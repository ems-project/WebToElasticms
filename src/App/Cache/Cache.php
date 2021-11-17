<?php

declare(strict_types=1);

namespace App\Cache;

use App\Config\Config;

class Cache
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }
}
