<?php
namespace Onion\Cli\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Http\Client\Curl\Client;
use Composer\CaBundle\CaBundle;


class HttpClientFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();
        $options = [
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        if (defined('CURLOPT_SSL_VERIFYSTATUS')) {
            $options[CURLOPT_SSL_VERIFYSTATUS] = true;
        }
        if (is_dir($caPathOrFile) || (is_link($caPathOrFile) && is_dir(readlink($caPathOrFile)))) {
            $options[CURLOPT_CAPATH] = $caPathOrFile;
        } else {
            $options[CURLOPT_CAINFO] = $caPathOrFile;
        }

        return new Client(null, null, $options);
    }
}
