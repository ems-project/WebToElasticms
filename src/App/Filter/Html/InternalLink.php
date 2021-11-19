<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\Config;
use App\Helper\Url;
use Symfony\Component\DomCrawler\Crawler;

class InternalLink
{
    public const TYPE = 'internal-link';
    private Config $config;
    private string $currentUrl;

    public function __construct(Config $config, string $currentUrl)
    {
        $this->config = $config;
        $this->currentUrl = $currentUrl;
    }

    public function process(Crawler $content): void
    {
        $this->convertAttribute($content, 'src');
        $this->convertAttribute($content, 'href');
    }

    protected function convertAttribute(Crawler $content, string $attribute): void
    {
        foreach ($content->filter("[$attribute]") as $item) {
            if (!$item instanceof \DOMElement) {
                throw new \RuntimeException('Unexpected non DOMElement object');
            }

            $href = $item->getAttribute($attribute);
            $url = new Url($href, $this->currentUrl);
            if (!\in_array($url->getHost(), $this->config->getHosts())) {
                continue;
            }
            $path = $this->config->findInternalLink($url);

            $item->setAttribute($attribute, $path);
        }
    }
}
