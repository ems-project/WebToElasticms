<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\ConfigManager;
use App\Helper\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class InternalLink
{
    public const TYPE = 'internal-link';
    private ConfigManager $config;
    private string $currentUrl;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, ConfigManager $config, string $currentUrl)
    {
        $this->config = $config;
        $this->currentUrl = $currentUrl;
        $this->logger = $logger;
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

            if (\in_array($url->getScheme(), ['mailto'])) {
                continue;
            }
            if (!\in_array($url->getHost(), $this->config->getHosts())) {
                continue;
            }
            $path = $url->getPath();
            if ($this->isLinkToRemove($item, $path)) {
                continue;
            }
            try {
                $path = $this->config->findInternalLink($url);
                $item->setAttribute($attribute, $path);
            } catch (\Throwable $e) {
                $this->logger->warning(\sprintf('Error while getting the resource %s with message %s', $url->getUrl(), $e->getMessage()));
            }
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
