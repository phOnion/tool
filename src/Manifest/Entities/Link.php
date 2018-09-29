<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Link implements Entity
{
    private $title;
    private $href;
    private $lang;

    public function __construct(string $title, string $href, ?string $lang = null)
    {
        $this->title = $title;
        $this->href = $href;
        $this->lang = $lang;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function hasLang(): bool
    {
        return $this->lang !== null;
    }

    public function jsonSerialize()
    {
        $link = [
            'title' => $this->getTitle(),
            'href' => $this->getHref(),
        ];

        if ($this->hasLang()) {
            $link['lang'] = $this->getLang();
        }

        return $link;
    }
}
