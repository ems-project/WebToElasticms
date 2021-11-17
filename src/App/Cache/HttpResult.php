<?php

declare(strict_types=1);

namespace App\Cache;

use App\Config\WebResource;
use Psr\Http\Message\ResponseInterface;

class HttpResult
{
    private WebResource $resource;
    private ResponseInterface $get;

    public function __construct(WebResource $resource, ResponseInterface $get)
    {
        $this->resource = $resource;
        $this->get = $get;
    }
}
