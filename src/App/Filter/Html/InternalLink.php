<?php

declare(strict_types=1);

namespace App\Filter\Html;

use App\Config\Config;
use Symfony\Component\DomCrawler\Crawler;

class InternalLink
{
    public const TYPE = 'internal-link';
    private Config $config;
    private string $relativePath;

    public function __construct(Config $config, string $currentUrl)
    {
        $this->config = $config;
        $parsedUrl = \parse_url($currentUrl);
        if (false === $parsedUrl) {
            throw new \RuntimeException(\sprintf('Unexpected wrong link %s', $currentUrl));
        }

        $relativePath = $parsedUrl['path'] ?? null;
        if (!\is_string($relativePath)) {
            throw new \RuntimeException(\sprintf('Unexpected non string path %s', $relativePath));
        }

        $this->relativePath = $relativePath;
        if ('/' !== \substr($this->relativePath, \strlen($this->relativePath) - 1)) {
            $this->relativePath .= '/';
        }
    }

    public function process(Crawler $content): void
    {
        $this->convertAttribute($content, 'src');
        $this->convertAttribute($content, 'href');
    }

    protected function convertAttribute(Crawler $content, string $attribute): void
    {
        foreach ($content->filter("[$attribute]") as $item) {
            if (!$item instanceof \DOMElement) {
                throw new \RuntimeException('Unexpected non DOMElement object');
            }

            $href = $item->getAttribute($attribute);
            $parsedUrl = \parse_url($href);
            if (false === $parsedUrl) {
                throw new \RuntimeException(\sprintf('Unexpected wrong link %s', $href));
            }
            if (isset($parsedUrl['host']) && !\in_array($parsedUrl['host'], $this->config->getHosts())) {
                continue;
            }
            $path = $this->getAbsolutePath($parsedUrl);
            $path = $this->config->findInternalLink($path, $parsedUrl['fragment'] ?? null, $parsedUrl['query'] ?? null);

            $item->setAttribute($attribute, $path);
        }
    }

    /**
     * @param array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, path?: string, query?: string, fragment?: string} $parsedUrl
     */
    private function getAbsolutePath(array $parsedUrl): string
    {
        $path = $parsedUrl['path'] ?? null;
        if (!\is_string($path)) {
            throw new \RuntimeException(\sprintf('Unexpected non string path %s', $path));
        }
        if ('.' === \substr($path, 0, 1)) {
            $path = $this->relativePath.$path;
        }
        if (!\is_string($path)) {
            throw new \RuntimeException(\sprintf('Unexpected non string path %s', $path));
        }
        $re = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];
        for ($n = 1; $n > 0;) {
            $path = \preg_replace($re, '/', $path, -1, $n);
            if (!\is_string($path)) {
                throw new \RuntimeException(\sprintf('Unexpected non string path %s', $path));
            }
        }

        return $path;
    }
}
