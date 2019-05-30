<?php declare(strict_types=1);
namespace Onion\Tool\Publish;

use GuzzleHttp\Psr7\Request;
use Http\Client\Curl\Client;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Entities\Repository;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    /** @var Client $client */
    private $client;

    /** @var Loader $loader */
    private $loader;

    public function __construct(Loader $loader, Client $client)
    {
        $this->loader = $loader;
        $this->client = $client;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $manifest = $this->loader->getManifest(getcwd());
        $repos = $manifest->getRepositories();

        $target = $console->getArgument('target', '@main');
        /** @var Repository $repo */
        $repo = $manifest->getRepositoryByName($target);
        if ($repo === null) {
            $console->writeLine("%text:red% Repository '{$target}' does not exist");
            return 1;
        }

        $file = $console->getArgument('artefact');
        if (!file_exists($file)) {
            $file .= '.phar';
        }

        if (!file_exists($file)) {
            $console->writeLine('%text:red% Unable to find artefact, please check the path');
            return 1;
        }

        $phar = new \Phar($file);
        $package = json_decode(file_get_contents($phar['onion.json']->getPathName()), true)['name'];

        $request = new Request('PUT', "{$repo->getUrl()}/packages/{$package}/artefact", [
            'content-type' => 'application/octet-stream',
            'accept' => 'application/json',
        ], fopen($file, 'rb'));

        $header = null;
        switch ($console->getArgument('auth', 'none')) {
            case 'jwt':
                $request = $request->withHeader(
                    'Authorization',
                    'Bearer ' . ($console->getArgument('secret') ?? $console->password('JWT Token'))
                );
                break;
            case 'token':
                $request = $request->withHeader(
                    'Authorization',
                    'Bearer ' . ($console->getArgument('secret') ?? $console->password('Token'))
                );
                break;
            case 'password':
                $credential = $console->getArgument(
                    'credential',
                    $console->prompt('Username', get_current_user())
                );
                $secret = $console->getArgument(
                    'secret',
                    $console->password('Password')
                );

                $request = $request->withHeader(
                    'Authorization',
                    'Basic ' . base64_encode("{$credential}:{$secret}")
                );
                break;
        }

        try {
            $response = $this->client->sendRequest($request);
            switch ($response->getStatusCode()) {
                case 200:
                case 201:
                    // @todo: print URL
                    $console->writeLine('%text:green%Artefact published successfully');
                    break;
                case 202:
                case 204:
                    $console->writeLine('%text:cyan%Yur upload is being processed and should be available shortly');
                    break;
                case 401:
                    $console->writeLine('%text:red%You are not authorized to publish this package');
                    return 1;
                    break;
                case 404:
                    $console->writeLine('%text:yellow%The package which you are trying to publish does not exist');
                    return 1;
                    break;
                default:
                    $console->writeLine('%text:red%Error while publishing, please try again later');
                    return 1;
                    break;
            }
        } catch (\Throwable $ex) {
            $console->writeLine("%bg:red%%text:white%{$ex->getMessage()}");
            return 1;
        };

        return 0;
    }
}
