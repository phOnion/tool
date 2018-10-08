<?php declare(strict_types=1);
namespace Onion\Cli\Config\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Onion\Cli\Config\Loader;
use Onion\Cli\Config\Reader\ReaderInterface;

class LoaderFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        $configDir = $container->has('config.directory') ? $container->get('config.directory') : getcwd() . '/config/';
        if (!is_dir($configDir)) {
            $configDir = getcwd();
        }

        $loader = new Loader($configDir);
        if ($container->has('config.readers')) {
            foreach ($container->get('config.readers') as $def) {
                $reader = $container->get($def['reader']);
                if (!$reader instanceof ReaderInterface) {
                    throw new \RuntimeException(sprintf(
                        'Invalid reader for extension(s): %s',
                        implode(', ', $def['extensions'])
                    ));
                }

                $loader->registerReader($def['extensions'], $reader);
            }
        }

        return $loader;
    }
}
