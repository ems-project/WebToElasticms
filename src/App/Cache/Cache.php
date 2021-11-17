<?php

declare(strict_types=1);

namespace App\Cache;

use App\Config\Root;

class Cache
{
    private Root $config;

    public function __construct(Root $config)
    {
        $this->config = $config;
    }
}
