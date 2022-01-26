<?php

declare(strict_types=1);

namespace App\Config;

class Extractor
{
    private string $selector;
    private ?string $attribute = null;
    private string $property;
    /** @var string[] */
    private array $filters = [];

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): void
    {
        $this->selector = $selector;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    /**
     * @return string[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param string[] $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function setAttribute(?string $attribute): void
    {
        $this->attribute = $attribute;
    }


}
