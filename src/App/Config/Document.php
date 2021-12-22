<?php

declare(strict_types=1);

namespace App\Config;

class Document
{
    /** @var WebResource[] */
    private array $resources;
    private string $type;
    private ?string $ouuid = null;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getOuuid(): ?string
    {
        return $this->ouuid;
    }

    public function setOuuid(?string $ouuid): void
    {
        $this->ouuid = $ouuid;
    }

    public function hasResourceFor(string $locale): bool
    {
        foreach ($this->resources as $resource) {
            if ($resource->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    public function addResource(WebResource $param): void
    {
        $this->resources[] = $param;
    }
}
