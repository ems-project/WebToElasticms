<?php

namespace App\Config;

class Computer
{
    private string $property;
    private string $template;
    private string $expression;
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

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression(string $expression): void
    {
        $this->expression = $expression;
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
