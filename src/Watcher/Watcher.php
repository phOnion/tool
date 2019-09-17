<?php
namespace Onion\Cli\Watcher;

use function Onion\Framework\Loop\coroutine;
use function Onion\Framework\Loop\read;

use Onion\Framework\Loop\Descriptor as Stream;
use Onion\Framework\Loop\Interfaces\ResourceInterface as StreamInterface;
use Onion\Framework\State\Interfaces\FlowInterface;

class Watcher
{
    private $flow;
    private $known = [];

    public function __construct(FlowInterface $flow)
    {
        $this->flow = $flow;
    }

    public function addDirectory(string $directory)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $this->addFile($file->getRealPath());
        }
    }

    public function addFile(string $file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("File {$file} does not exist");
        }

        if (!isset($this->known[$file])) {
            $this->known[$file] = md5_file($file);
            return;
        }

        foreach ($this->known as $key => $hash) {
            if (!file_exists($key)) {
                $this->flow->apply('delete', $this);
                unset($this->known[$key]);
                continue;
            }

            if ($hash !== md5_file($key)) {
                $this->flow->apply('change', $this);
                $this->known[$key] = md5_file($key);
            }
        }
    }
}
