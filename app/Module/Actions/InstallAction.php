<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

use GuzzleHttp\Psr7\Request;
use Http\Client\Curl\Client;
use Http\Client\Exception\NetworkException;
use Onion\Cli\Manifest\Entities\Dependency;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class InstallAction extends AbstractAction implements ActionInterface
{
    /** @var Loader $loader */
    private $loader;
    /** @var Client $client */
    private $client;

    public function __construct(Loader $loader, Client $client)
    {
        $this->loader = $loader;
        $this->client = $client;
    }

    public function perform(ConsoleInterface $console, string $module): int
    {
        $load = !$console->getArgument('no-load', false);
        if (file_exists(getcwd() . "/modules/{$module}")) {
            $manifest = $this->loader->getManifest(getcwd() . "/modules/{$module}");
            foreach ($manifest->getDependencies() as $dependency) {
                /** @var Dependency $dependency */
                if (!$this->install($dependency, $load)) {
                    $console->writeLine("%text:red%Unable to install '{$dependency->getName()}'");
                    return 1;
                }
            }

            return 0;
        }

        if (!$this->validateModule($module)) {
            $console->writeLine("%text:red%Invalid module name '{$module}'");
            return 1;
        }

        $dependency = new Dependency(
            $module,
            $console->getArgument('constraint', 'latest'),
            $console->getArgument('repo', '@main')
        );

        if ($this->install($console, $dependency, $load)) {
            $manifest = $this->loader->getManifest(getcwd() . "/modules/{$module}");
            $this->loader->saveManifest(getcwd(), $manifest->withAddedDependency($dependency));
        }

        return 1;
    }

    private function install(ConsoleInterface $console, Dependency $dependency, bool $load = true)
    {
        $file = getcwd() . "/modules/{$dependency->getName()}.phar";
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0777, true);
        }
        if ($console->getArgument('no-load')) {
            $file .= '.unloaded';
        }

        $manifest = $this->loader->getManifest();
        $url = $manifest->getRepositoryByName($console->getArgument('repo', '@main'));

        $name = urlencode($dependency->getName());
        $request = new Request('GET', $url->getUrl() . "/packages/{$name}?version={$dependency->getVersion()}", [
            'accept' => 'application/octet-stream'
        ]);
        try {
            $response = $this->client->sendRequest($request);
            switch ($response->getStatusCode()) {
                case 200:
                    file_put_contents($file, $response->getBody()->getContents());
                    break;
                case 404:
                    $console->writeLine(
                        "%text:yellow%Dependency '{$dependency->getName()}' was not found in repository '{$dependency->getRepository()}'"
                    );
                    return false;
                    break;
                case 401:
                    $console->writeLine("%text:red%Authentication required, please provide authorization data");
                    return 1;
                default:
                    $console->writeLine("%bg:red%Unable to download package {$dependency->getName()}");
                    if ($console->getArgument('verbose', false)) {
                        $console->writeLine("URL: {$request->getUri()} - {$response->getStatusCode()}");
                    }
                    return 1;
                    break;
            }
        } catch (NetworkException $ex) {
            $console->writeLine("{$ex->getMessage()}");
            return false;
        }

        return true;
    }
}
