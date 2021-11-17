<?php

declare(strict_types=1);

namespace App\Config;

class Document
{
    /** @var WebResource[] */
    private array $resources;

    /**
     * @return WebResource[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param WebResource[] $resources
     */
    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }
}
