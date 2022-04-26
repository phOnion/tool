<?php

namespace Onion\Cli\Manifest\Factory;

use Onion\Cli\Manifest\Loader;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

class ManifestLoaderFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        return new Loader([
            'links' => $container->get('manifest.map.links'),
            'repositories' => $container->get('manifest.map.repositories'),
            'dependencies' => $container->get('manifest.map.dependencies'),
        ]);
    }
}
