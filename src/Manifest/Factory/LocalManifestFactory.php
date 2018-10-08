<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Cli\Manifest\Loader;

class LocalManifestFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $loader = new Loader(
            $container->get('manifest.map')
        );

        $location = \Phar::running(true) === '' ?
            getcwd() : \Phar::running(true);

        return $loader->getManifest($location);
    }
}
