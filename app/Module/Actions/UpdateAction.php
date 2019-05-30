<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

use Http\Client\Curl\Client;
use Onion\Cli\Manifest\Entities\Dependency;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class UpdateAction extends InstallAction implements ActionInterface
{
    /** @var Loader $loader */
    private $loader;
    /** @var Client $client */
    private $client;

    public function __construct(Loader $loader, Client $client)
    {
        $this->loader = $loader;
        $this->client = $client;

        parent::__construct($loader, $client);
    }

    public function validateModule(string $module)
    {
        return true;
    }

    public function perform(ConsoleInterface $console, string $module): int
    {
        if ($module !== '') {
            return parent::perform($console, $module);
        }

        $manifest = $this->loader->getManifest();
        foreach ($manifest->getDependencies() as $dependency) {
            parent::perform($console->withArgument('constraint', 'latest'), $module);
        }

        return 0;
    }
}
