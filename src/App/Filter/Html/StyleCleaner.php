<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\Config;
use Symfony\Component\DomCrawler\Crawler;

class StyleCleaner
{
    public const TYPE = 'style-cleaner';
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function process(Crawler $content): Crawler
    {
        foreach ($content->filter('[style]') as $item) {
            if (!$item instanceof \DOMElement) {
                throw new \RuntimeException('Unexpected non DOMElement object');
            }
            $item->removeAttribute('style');
        }

        return $content;
    }
}
