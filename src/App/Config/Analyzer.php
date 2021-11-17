<?php

declare(strict_types=1);

namespace App\Config;

class Analyzer
{
    private string $name;
    /** @var Extractor[] */
    private array $extractors;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Extractor[]
     */
    public function getExtractors(): array
    {
        return $this->extractors;
    }

    /**
     * @param Extractor[] $extractors
     */
    public function setExtractors(array $extractors): void
    {
        $this->extractors = $extractors;
    }
}
