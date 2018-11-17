<?php declare(strict_types=1);
namespace Onion\Tool\Module;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Github\Client;
use Onion\Cli\SemVer\Version;
use Onion\Cli\Manifest\Entities\Dependency;
use Onion\Framework\Console\Progress;

class Command implements CommandInterface
{
    /** @var Loader $loader */
    private $loader;

    /** @var Manifest $manifest */
    private $manifest;

    /** @var Client $client */
    private $client;

    public function __construct(Loader $loader, Client $client)
    {
        $this->loader = $loader;
        $this->client = $client;
    }

    public function trigger(ConsoleInterface $console): int
    {
        // onion module install onion/framework
        $module = $console->getArgument('module');

        $action = $console->getArgument('action');

        $env = $console->getArgument('env', 'global');
        $manifestLocation = getcwd();
        if (is_file(getcwd() . "/{$module}") && in_array($action, ['install', 'load'])) {
            $manifestLocation = "phar://{$manifestLocation}/{$module}";
            if (!file_exists($manifestLocation)) {
                $console->writeLine("%text:red%Application file '{$manifestLocation}' does not exist");
                return 1;
            }
            $module = false;
        } else {
            list($vendor, $project)=array_map('strtolower', explode('/', $module ?? '//'));
        }


        $this->manifest = $this->loader->getManifest($manifestLocation);

        $deps = $this->manifest->getDependencies();

        if (!is_dir(getcwd() . '/config/')) {
            mkdir(getcwd() . '/config/', 0644);
        }

        $modulesFile = getcwd() . "/config/modules.{$env}.php";
        $modules = [];
        if (file_exists($modulesFile)) {
            $modules = include $modulesFile;
        }
        $alias = $console->getArgument('alias');
        switch ($action) {
            case 'install':
                if ($module) {
                    $constraint = $console->getArgument('constraint');
                    $this->installModule($console, $module, $constraint, $alias);
                    $modules = $this->loadModule($modules, $module, $alias);
                    break;
                }

                foreach ($deps as $dep) {
                    $this->installModule($console, $dep->getName(), $dep->getVersion(), $dep->getAlias());
                    $modules = $this->loadModule($modules, $dep->getName(), $alias);
                }
                break;
            case 'load':
                $modules = $this->loadModule($modules, $module, $alias);
                break;
            case 'uninstall':
                if ($module === null) {
                    $console->writeLine(
                        '%text:yellow% No module provided'
                    );
                }

                if (!file_exists(getcwd() . "/modules/{$vendor}/{$project}.phar")) {
                    $console->writeLine(
                        "%text:cyan%Module %text:bold-yellow%{$module}%text:cyan% does not exist"
                    );
                    return 1;
                }

                unlink(getcwd() . "/modules/{$vendor}/{$project}.phar");
                unlink(getcwd() . "/modules/{$vendor}/{$project}.json");
                if (count(scandir(getcwd() . "/modules/{$vendor}")) === 2) {
                    @rmdir(getcwd() . "/modules/{$vendor}");
                }
                foreach ($deps as $index => $dep) {
                    if ($dep->getName() === $module) {
                        unset($deps[$index]);
                        break;
                    }
                }
                $this->loader->saveManifest(getcwd(), $this->manifest->withDependencies($deps));
            case 'unload':
                if (!isset($modules['modules']["module:$project"])) {
                    $console->writeLine(
                        "%text:yellow%Unable to unload module '{$module}'. Not found"
                    );
                }

                unset($modules['modules']["module:$module"]);
                unset($modules['factories']["module:$module"]);
                break;
            default:
                $console->writeLine(
                    "%text:yellow%Unknown command %text:red%{$module}"
                );
        }

        file_put_contents($modulesFile, '<?php return ' . var_export($modules, true) . ';');
        return 0;
    }

    private function installModule(
        ConsoleInterface $console,
        string $module,
        ?string $constraint = null,
        ?string $alias = null
    ) {
        list($vendor, $project)=array_map('strtolower', explode('/', $module));
        $installDir = getcwd() . "/modules/{$vendor}";
        $installFile = "{$installDir}/{$project}.phar";

        if (file_exists($installFile)) {
            return;
        }

        $releases = $this->client->api('repo')->releases()->all($vendor, $project);
        foreach ($releases as $release) {
            $ver = ltrim($release['tag_name'], 'v');
            $version = new Version($ver);
            if ($version->satisfies($constraint ?? "^{$ver}")) {
                $console->write(
                    "%text:cyan%Installing %text:green%{$module}@{$version}"
                );
                $console->write('   ');
                $assets = $this->client->api('repo')
                    ->releases()
                    ->assets()
                    ->all($vendor, $project, $release['id']);

                foreach ($assets as $asset) {
                    if (stripos($asset['name'], '.phar') !== false) {
                        if (!is_dir($installDir)) {
                            @mkdir($installDir, 0755, true);
                        }
                        $fp = fopen($installFile, 'wb');

                        $progress = new Progress(50, 100);
                        $progress->setFormat("%text:cyan%{progress}%");

                        $ch = curl_init($asset['browser_download_url']);
                        curl_setopt($ch, CURLOPT_HEADER, false);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_URL, $asset['browser_download_url']);
                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
                        curl_setopt(
                            $ch,
                            CURLOPT_PROGRESSFUNCTION,
                            function($resource, $download_size, $downloaded) use ($console, $progress) {
                                if($download_size > 0 && (round($downloaded / $download_size, 0) * 100)) {
                                    $progress->increment(1);
                                }
                                $progress->display($console);
                            });
                        curl_exec($ch);
                        curl_close($ch);
                        fclose($fp);
                        $console->writeLine('');

                        // @ToDo: build dependency graph and circular reference resolution
                        $dependencyManifest = $this->loader->getManifest("phar://{$installFile}");
                        foreach ($dependencyManifest->getDependencies() as $dependency) {
                            /** @var Dependency $dependency*/
                            $fail = $this->trigger(
                                $console->withArgument('action', 'install')
                                    ->withArgument('module', $dependency->getName())
                                    ->withArgument('alias', $dependency->getAlias())
                                    ->withArgument('constraint', $dependency->getVersion())
                            );
                            if ($fail) {
                                exit($fail);
                            }
                        }

                        break;
                    }
                }

                $deps[] = new Dependency($module, (string) $version, $alias);
                $this->loader->saveManifest(getcwd(), $this->manifest->withDependencies($deps));
                $installed = new \Phar($installFile);
                $meta = $installed->getMetadata();
                if (($meta['standalone'] ?? false)) {
                    $console->writeLine('%text:red%Not a module, uninstalling');
                    unlink($installFile);
                    return 1;
                }

                $composer = json_decode(file_get_contents("phar://{$installFile}/composer.json"), true);
                file_put_contents("{$installDir}/{$project}.json", json_encode([
                    'require' => $composer['require'],
                ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                break;
            }
        }
    }

    private function loadModule(array $modules, string $module, string $alias = null): array
    {
        list($vendor, $project)=array_map('strtolower', explode('/', $module));
        if ($alias === null) {
            $alias = $module;
        }

        $modules['modules']["module:{$alias}"] = "modules/{$vendor}/{$project}.phar";

        return $modules;
    }
}
