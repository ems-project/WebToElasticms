<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\Config;
use Symfony\Component\DomCrawler\Crawler;

class InternalLink
{
    public const TYPE = 'internal-link';
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function process(Crawler $content): void
    {
    }
}
