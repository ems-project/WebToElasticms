<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\ConfigManager;
use App\Helper\Url;
use Symfony\Component\DomCrawler\Crawler;

class InternalLink
{
    public const TYPE = 'internal-link';
    private ConfigManager $config;
    private string $currentUrl;

    public function __construct(ConfigManager $config, string $currentUrl)
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
            $path = $url->getPath();
            if ($this->isLinkToRemove($item, $path)) {
                continue;
            }
            $path = $this->config->findInternalLink($url);

            $item->setAttribute($attribute, $path);
        }
    }

    private function isLinkToRemove(\DOMNode $item, string $path): bool
    {
        foreach ($this->config->getLinkToClean() as $regex) {
            if (\preg_match($regex, $path)) {
                $parent = $item->parentNode;
                if (!$parent instanceof \DOMElement) {
                    throw new \RuntimeException('Unexpected non DOMElement object');
                }
                $document = $item->ownerDocument;
                if (!$document instanceof \DOMDocument) {
                    throw new \RuntimeException('Unexpected non DOMDocument object');
                }
                $textNode = $document->createTextNode($item->nodeValue);
                $parent->replaceChild($textNode, $item);

                return true;
            }
        }

        return false;
    }
}
