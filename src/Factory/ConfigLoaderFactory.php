<?php
namespace Onion\Cli\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Common\Config\Loader;

class ConfigLoaderFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $loader = new Loader();

        foreach ($container->get('config.readers') as $config) {
            $loader->registerReader($config['extensions'], $container->get($config['reader']));
        }

        return $loader;
    }
}
