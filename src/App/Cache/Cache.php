<?php

declare(strict_types=1);

namespace App\Cache;

use App\Config\Config;

class Cache
{
    private Config $config;
    private string $cacheFolder;

    public function __construct(Config $config, string $cacheFolder)
    {
        $this->config = $config;
        $this->cacheFolder = $cacheFolder;
    }
}
