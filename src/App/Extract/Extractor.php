<?php

declare(strict_types=1);

namespace App\Extract;

use App\Cache\CacheManager;
use App\Config\ConfigManager;
use App\Config\WebResource;

class Extractor
{
    private ConfigManager $config;
    private CacheManager $cache;

    public function __construct(ConfigManager $config, CacheManager $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    public function extractDataCount(): int
    {
        return \count($this->config->getDocuments());
    }

    /**
     * @return iterable<mixed[]>
     */
    public function extractData(): iterable
    {
        foreach ($this->config->getDocuments() as $document) {
            $data = [];
            foreach ($document->getResources() as $resource) {
                $this->extractDataFromResource($resource, $data);
            }
            yield $data;
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function extractDataFromResource(WebResource $resource, array &$data): void
    {
        $result = $this->cache->get($resource->getUrl());
        $analyzer = $this->config->getAnalyzer($resource->getType());
        switch ($analyzer->getType()) {
            case Html::TYPE:
                $extractor = new Html($this->config);
                break;
            default:
                throw new \RuntimeException(\sprintf('Type of analyzer %s unknown', $analyzer->getType()));
        }
        $extractor->extract($resource, $result, $analyzer, $data);
    }
}
