<?php

namespace App\Config;

class Computer
{
    private string $property;
    private string $template;
    private bool $jsonDecode = false;
    private bool $jsonEscape = false;

    public function getProperty(): string
    {
        return $this->property;
    }

    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function isJsonDecode(): bool
    {
        return $this->jsonDecode;
    }

    public function setJsonDecode(bool $jsonDecode): void
    {
        $this->jsonDecode = $jsonDecode;
    }

    public function isJsonEscape(): bool
    {
        return $this->jsonEscape;
    }

    public function setJsonEscape(bool $jsonEscape): void
    {
        $this->jsonEscape = $jsonEscape;
    }
}
