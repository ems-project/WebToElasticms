<?php

declare(strict_types=1);

namespace App\Extract;

use App\Cache\Cache;
use App\Config\Config;
use App\Config\WebResource;
use Symfony\Component\Console\Helper\ProgressBar;

class Extractor
{
    private Config $config;
    private Cache $cache;

    public function __construct(Config $config, Cache $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    public function extractDataCount(): int
    {
        return \count($this->config->getDocuments());
    }

    public function extractData(ProgressBar $createProgressBar): void
    {
        foreach ($this->config->getDocuments() as $document) {
            $data = [];
            foreach ($document->getResources() as $resource) {
                $this->extractDataFromResource($resource, $data);
            }
            \dump($data);
            $createProgressBar->advance();
        }
        $createProgressBar->finish();
    }

    /**
     * @param array<mixed> $data
     */
    private function extractDataFromResource(WebResource $resource, array &$data): void
    {
        $result = $this->cache->get($resource);
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
