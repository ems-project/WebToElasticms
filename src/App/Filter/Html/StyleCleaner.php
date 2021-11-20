<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\ConfigManager;
use Symfony\Component\DomCrawler\Crawler;

class StyleCleaner
{
    public const TYPE = 'style-cleaner';
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    public function process(Crawler $content): void
    {
        foreach ($content->filter('[style]') as $item) {
            if (!$item instanceof \DOMElement) {
                throw new \RuntimeException('Unexpected non DOMElement object');
            }
            $item->removeAttribute('style');
        }
    }
}
