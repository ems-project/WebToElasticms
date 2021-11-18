<?php

declare(strict_types=1);

namespace App\Cache;

use Psr\Http\Message\ResponseInterface;

class HttpResult
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
