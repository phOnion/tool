<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Cli\Manifest\Loader;

class LocalManifestFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $location = \Phar::running(true) === '' ?
            getcwd() : \Phar::running(true);

        return $container->get(Loader::class)->getManifest($location);
    }
}
