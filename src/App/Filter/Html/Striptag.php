<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\ConfigManager;
use App\Config\WebResource;
use Symfony\Component\DomCrawler\Crawler;

class Striptag implements HtmlInterface
{
    public const TYPE = 'striptags';
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    public function process(WebResource $resource, Crawler $content): void
    {
    }
}
