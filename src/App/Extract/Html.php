<?php

declare(strict_types=1);

namespace App\Extract;

use App\Cache\HttpResult;
use App\Config\Analyzer;
use App\Config\Computer;
use App\Config\ConfigManager;
use App\Config\WebResource;
use App\Filter\Html\ClassCleaner;
use App\Filter\Html\InternalLink;
use App\Filter\Html\Striptag;
use App\Filter\Html\StyleCleaner;
use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Html
{
    public const TYPE = 'html';
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<mixed> $data
     */
    public function buildData(WebResource $resource, HttpResult $result, Analyzer $analyzer, array &$data): void
    {
        $crawler = new Crawler($result->getResponse()->getBody()->getContents());
        foreach ($analyzer->getExtractors() as $extractor) {
            $content = $crawler->filter($extractor->getSelector());
            $html = $this->applyFilters($resource, $content, $extractor);
            $this->assignExtractedProperty($resource, $extractor, $data, $html);
        }
        foreach ($analyzer->getComputers() as $computer) {
            $value = $this->compute($computer, $data);
            $this->assignComputedProperty($computer, $data, $value);
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

    /**
     * @param array<mixed> $data
     *
     * @return string|array<mixed>|null
     */
    private function compute(Computer $computer, array &$data)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $value = \preg_replace_callback(
            '/%(?P<key>[a-z\-\_0-9\.\[\]]+)%/i',
            function ($matches) use (&$data, $propertyAccessor, $computer) {
                $propertyPath = Document::fieldPathToPropertyPath($matches['key']);
                $value = $propertyAccessor->getValue($data, $propertyPath);
                if (null === $value) {
                    return '';
                }
                if ($computer->isJsonEscape()) {
                    return Json::escape(\strval($value));
                }

                return \strval($value);
            },
            $computer->getTemplate()
        );

        if (null === $value) {
            throw new \RuntimeException('Unexpected null value');
        }

        if ($computer->isJsonDecode()) {
            if ('null' === \trim($value)) {
                return null;
            }

            return Json::decode($value);
        }

        return $value;
    }

    /**
     * @param array<mixed>             $data
     * @param string|array<mixed>|null $value
     */
    private function assignComputedProperty(Computer $computer, array &$data, $value): void
    {
        $property = Document::fieldPathToPropertyPath($computer->getProperty());
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($data, $property, $value);
    }
}
