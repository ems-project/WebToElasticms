<?php

declare(strict_types=1);

namespace App\Extract;

use App\Cache\HttpResult;
use App\Config\Analyzer;
use App\Config\ConfigManager;
use App\Config\Document;
use App\Config\WebResource;
use App\Filter\Html\ClassCleaner;
use App\Filter\Html\InternalLink;
use App\Filter\Html\Striptag;
use App\Filter\Html\StyleCleaner;
use App\Helper\Url;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Html
{
    public const TYPE = 'html';
    private ConfigManager $config;
    private Document $document;

    public function __construct(ConfigManager $config, Document $document)
    {
        $this->config = $config;
        $this->document = $document;
    }

    /**
     * @param array<mixed> $data
     */
    public function extractData(WebResource $resource, HttpResult $result, Analyzer $analyzer, array &$data): void
    {
        $crawler = new Crawler($result->getResponse()->getBody()->getContents());
        $this->autoDiscoverResources($crawler, $resource);
        foreach ($analyzer->getExtractors() as $extractor) {
            $content = $crawler->filter($extractor->getSelector());
            $html = $this->applyFilters($resource, $content, $extractor);
            $this->assignExtractedProperty($resource, $extractor, $data, $html);
        }
    }

    /**
     * @param array<mixed> $data
     */
    protected function assignExtractedProperty(WebResource $resource, \App\Config\Extractor $extractor, array &$data, string $html): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $property = \str_replace(['%locale%'], [$resource->getLocale()], $extractor->getProperty());
        $propertyAccessor->setValue($data, $property, $html);
    }

    private function applyFilters(WebResource $resource, Crawler $content, \App\Config\Extractor $extractor): string
    {
        $asHtml = true;
        foreach ($extractor->getFilters() as $filterType) {
            switch ($filterType) {
                case Striptag::TYPE:
                    $filter = new Striptag($this->config);
                    $asHtml = false;
                    break;
                case InternalLink::TYPE:
                    $filter = new InternalLink($this->config, $resource->getUrl());
                    break;
                case StyleCleaner::TYPE:
                    $filter = new StyleCleaner($this->config);
                    break;
                case ClassCleaner::TYPE:
                    $filter = new ClassCleaner($this->config);
                    break;
                default:
                    throw new \RuntimeException(\sprintf('Unexpected %s filter', $filterType));
            }
            $filter->process($content);
        }

        return $asHtml ? $content->html() : $content->text();
    }

    private function autoDiscoverResources(Crawler $crawler, WebResource $resource): void
    {
        $cssSelector = $this->config->getAutoDiscoverResourcesLink();
        if (null === $cssSelector) {
            return;
        }
        foreach ($this->config->getLocales() as $locale) {
            if ($this->document->hasResourceFor($locale)) {
                continue;
            }
            $content = $crawler->filter(\str_replace('%locale%', $locale, $cssSelector));
            if (1 !== $content->count()) {
                continue;
            }
            $path = $content->attr('href');
            if (!\is_string($path)) {
                continue;
            }
            $pattern = $this->config->getIgnoreResourceLinkPattern();
            if (null !== $pattern && \preg_match($pattern, $path)) {
                continue;
            }
            $url = new Url($path, $resource->getUrl());
            $this->document->addResource(new WebResource($url->getUrl(), $locale, $resource->getType()));
        }
    }
}
