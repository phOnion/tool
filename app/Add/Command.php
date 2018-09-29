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
        if (!$console->hasArgument('type')) {
            throw new \InvalidArgumentException('No explicit type provided.');
        }

        $type = $console->getArgument('type', 'null');
        try {
            $strategy = $this->getDelegatedStrategyByType($type);
            $this->getLoader()->saveManifest(
                getcwd(),
                $strategy->addValue(
                    $this->getLoader()->getManifest(),
                    $strategy->prompt($console)
                )
            );

            return 0;
        } catch (\UnexpectedValueException $ex) {
            $console->writeLine(
                "%text:yellow%Unable to add new component. Unknown type '$type'"
            );
            throw $ex;
        }

        return 1;
    }
}
