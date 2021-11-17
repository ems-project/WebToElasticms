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
            foreach ($document->getResources() as $resource) {
                $this->extractDataFromResource($resource);
            }
            $createProgressBar->advance();
        }
        $createProgressBar->finish();
    }

    private function extractDataFromResource(WebResource $resource): void
    {
        $result = $this->cache->get($resource);

    }
}
