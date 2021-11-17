<?php

namespace App\Extract;

use App\Cache\HttpResult;
use App\Config\Analyzer;
use App\Config\WebResource;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Html
{
    public const TYPE = 'html';

    /**
     * @param array<mixed> $data
     */
    public static function extract(WebResource $resource, HttpResult $result, Analyzer $analyzer, array &$data): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $crawler = new Crawler($result->getResponse()->getBody()->getContents());
        foreach ($analyzer->getExtractors() as $extractor) {
            $html = $crawler->filter($extractor->getSelector())->html();
            $property = str_replace(['%locale%'], [$resource->getLocale()], $extractor->getProperty());
            $propertyAccessor->setValue($data, $property, $html);
        }
    }


}
