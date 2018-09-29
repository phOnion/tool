<?php declare(strict_types=1);
namespace App\Add;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Loader;

class Command implements CommandInterface
{
    private $delegate;
    private $loader;

    public function __construct(Loader $loader, Service\DelegateService $delegate)
    {
        $this->loader = $loader;
        $this->delegate = $delegate;
    }

    public function getLoader(): Loader
    {
        return $this->loader;
    }

    public function getDelegatedStrategyByType(string $type): StrategyInterface
    {
        return $this->delegate->getStrategy($type);
    }

    public function trigger(ConsoleInterface $console): int
    {
        // if (!$console->hasArgument('type')) {

        // }
        $type = $console->getArgument('type', 'null');
        try {
            $strategy = $this->getDelegatedStrategyByType($type);
            return (int) !$this->getLoader()->saveManifest(
                getcwd(),
                $strategy->addValue(
                    $this->getLoader()->getManifest(),
                    $strategy->prompt($console)
                )
            );
        } catch (\UnexpectedValueException $ex) {
            $console->writeLine(
                "%text:yellow%Unable to add new component. Unknown type '$type'"
            );
            throw $ex;
        }

        return 1;
    }
}