<?php declare(strict_types=1);
namespace Onion\Tool\Publish;

use Github\Client;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Loader;
use Onion\Cli\SemVer\MutableVersion;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    /** @var Client $client */
    private $client;

    /** @var Loader $loader */
    private $loader;

    public function __construct(Client $client, Loader $loader)
    {
        $this->client = $client;
        $this->loader = $loader;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $manifest = $this->loader->getManifest(getcwd());

        $repos = $manifest->getRepositories();
        if ($console->hasArgument('target')) {
            $target = $console->getArgument('target');
            $repos = array_filter($repos, function ($repo) use ($target) {
                return $repo->getName() === $target;
            });
        }

        $repo = array_shift($repos);

        $version = new MutableVersion($manifest->getVersion());
        $file = "{$console->getArgument('filename')}.phar";

        $credential = null;
        $secret = null;
        $method = Client::AUTH_HTTP_PASSWORD;
        switch ($console->getArgument('auth', 'password')) {
            case 'jwt':
                $credential = $console->getArgument('secret') ?? $console->password('JWT Token');
                $method = Client::AUTH_JWT;
                break;
            case 'token':
                $credential = $console->getArgument('secret') ?? $console->password('Token');
                $method = Client::AUTH_HTTP_TOKEN;
                break;
            case 'password':
            default:
                $credential = $console->getArgument(
                    'credential',
                    $console->prompt('Username', get_current_user())
                );
                $secret = $console->getArgument(
                    'secret',
                    $console->password('Password')
                );
                break;
        }

        $this->client->authenticate($credential, $secret, $method);
        try {
            $release = $this->client->api('repo')
                ->releases()
                ->create($repo->getVendor(), $repo->getProject(), [
                    'tag_name' => (string) $version,
                    'name' => (string) $version,
                    'target_commitish' => $repo->getBranch(),
                    'prerelease' => $version->isPreRelease(),
                    'draft' => $console->hasArgument('draft'),
                ]);

            $this->client->api('repo')
                ->releases()
                ->assets()
                ->create(
                    $repo->getVendor(),
                    $repo->getProject(),
                    $release['id'],
                    $file,
                    'application/octet-stream',
                    file_get_contents($console->getArgument('artefact'))
                );

            $console->writeLine('%text:green%Release published successfully');
        } catch (\Exception $ex) {
            $console->writeLine('%text:red%Unable to publish');
            throw $ex;
        }

        return 0;
    }
}
