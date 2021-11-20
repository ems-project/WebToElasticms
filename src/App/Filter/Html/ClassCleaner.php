<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\ConfigManager;
use Symfony\Component\DomCrawler\Crawler;

class ClassCleaner
{
    public const TYPE = 'class-cleaner';
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    public function process(Crawler $content): void
    {
        foreach ($content->filter('[class]') as $item) {
            if (!$item instanceof \DOMElement) {
                throw new \RuntimeException('Unexpected non DOMElement object');
            }
            $classes = \explode(' ', $item->getAttribute('class'));
            $classes = \array_intersect($classes, $this->config->getValidClasses());
            if (empty($classes)) {
                $item->removeAttribute('class');
            } else {
                $item->setAttribute('class', \implode(' ', $classes));
            }
        }
    }
}
