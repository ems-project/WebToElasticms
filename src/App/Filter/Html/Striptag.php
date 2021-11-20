<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\ConfigManager;
use Symfony\Component\DomCrawler\Crawler;

class Striptag
{
    public const TYPE = 'striptags';
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    public function process(Crawler $content): void
    {
    }
}
