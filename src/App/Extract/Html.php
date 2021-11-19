<?php

declare(strict_types=1);

namespace App\Extract;

use App\Cache\HttpResult;
use App\Config\Analyzer;
use App\Config\Config;
use App\Config\WebResource;
use App\Filter\Html\InternalLink;
use App\Filter\Html\Striptag;
use App\Filter\Html\StyleCleaner;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Html
{
    public const TYPE = 'html';
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<mixed> $data
     */
    public function extract(WebResource $resource, HttpResult $result, Analyzer $analyzer, array &$data): void
    {
        $crawler = new Crawler($result->getResponse()->getBody()->getContents());
        foreach ($analyzer->getExtractors() as $extractor) {
            $content = $crawler->filter($extractor->getSelector());
            $html = $this->applyFilters($content, $extractor);
            $this->assignProperty($resource, $extractor, $data, $html);
        }
    }

    /**
     * @param array<mixed> $data
     */
    protected function assignProperty(WebResource $resource, \App\Config\Extractor $extractor, array &$data, string $html): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $property = \str_replace(['%locale%'], [$resource->getLocale()], $extractor->getProperty());
        $propertyAccessor->setValue($data, $property, $html);
    }

    private function applyFilters(Crawler $content, \App\Config\Extractor $extractor): string
    {
        $asHtml = true;
        foreach ($extractor->getFilters() as $filterType) {
            switch ($filterType) {
                case Striptag::TYPE:
                    $filter = new Striptag($this->config);
                    $asHtml = false;
                    break;
                case InternalLink::TYPE:
                    $filter = new InternalLink($this->config);
                    break;
                case StyleCleaner::TYPE:
                    $filter = new StyleCleaner($this->config);
                    break;
                default:
                    throw new \RuntimeException(\sprintf('Unexpected %s filter', $filterType));
            }
            $filter->process($content);
        }

        return $asHtml ? $content->html() : $content->text();
    }
}
