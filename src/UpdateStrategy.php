<?php
namespace Onion\Cli;

use GuzzleHttp\Psr7\Request;
use Http\Client\Curl\Client;
use Http\Client\Exception\NetworkException;
use Humbug\SelfUpdate\Exception\HttpRequestException;
use Humbug\SelfUpdate\Strategy\StrategyInterface;
use Humbug\SelfUpdate\Updater;
use Onion\Cli\Manifest\Entities\Manifest;


class UpdateStrategy implements StrategyInterface
{
    /** @var Manifest $manifest */
    private $manifest;
    /** @var Client $client */
    private $client;

    public function __construct(Manifest $manifest, Client $client)
    {
        $this->manifest = $manifest;
        $this->client = $client;
    }

    public function getCurrentLocalVersion(Updater $updater)
    {
        return $this->manifest->getVersion();
    }

    public function getCurrentRemoteVersion(Updater $updater)
    {
        $repo = $this->manifest->getRepositoryByName('@main');
        $request = new Request(
            'GET',
            "{$repo->getUrl()}/latest",
            [
                'accept' => 'text/plain'
            ]
        );

        try {
            $response = $this->client->sendRequest($request);
            switch ($response->getStatusCode()) {
                case 200:
                    return $response->getBody()->getContents();
                    break;
                default:
                    throw new HttpRequestException(
                        'Failed to retrieve latest version'
                    );
                    break;
            }

        } catch (NetworkException $ex) {
            throw new HttpRequestException(
                $ex->getMessage(), $ex->getCode(), $ex
            );
        }
    }

    public function download(Updater $updater)
    {
        $repo = $this->manifest->getRepositoryByName('@main');
        $request = new Request(
            'GET',
            "{$repo->getUrl()}/latest",
            [
                'accept' => 'application/octet-stream'
            ]
        );
        try {
            $response = $this->client->sendRequest($request);
            switch ($response->getStatusCode()) {
                case 200:
                    file_put_contents(
                        $updater->getTempPharFile(), $response->getBody()->getContents()
                    );
                    break;
                default:
                    throw new HttpRequestException(
                        "[{$response->getStatusCode()}] Request to URL failed: {$request->getUri()} "
                    );
                    break;
            }
        } catch (NetworkException $ex) {
            throw new HttpRequestException(
                $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }
    }
}
