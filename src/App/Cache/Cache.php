<?php

declare(strict_types=1);

namespace App\Cache;

use App\Config\Config;
use App\Config\WebResource;
use Symfony\Component\Console\Helper\ProgressBar;

class Cache
{
    private Config $config;
    private string $cacheFolder;

    public function __construct(Config $config, string $cacheFolder)
    {
        $this->config = $config;
        $this->cacheFolder = $cacheFolder;
    }

    public function refreshMax(): int
    {
        return \count($this->config->getDocuments());
    }

    public function refresh(ProgressBar $createProgressBar): void
    {
        foreach ($this->config->getDocuments() as $document) {
            foreach ($document->getResources() as $resource) {
                $this->refreshResource($resource);
            }
            $createProgressBar->advance();
        }
        $createProgressBar->finish();
    }

    private function refreshResource(WebResource $resource): void
    {
    }
}
