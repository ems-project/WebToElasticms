<?php

declare(strict_types=1);

namespace App\Filter\Attr;

use App\Config\ConfigManager;
use App\Helper\Url;
use Psr\Log\LoggerInterface;

class Src
{
    public const TYPE = 'src';
    private ConfigManager $config;
    private string $currentUrl;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, ConfigManager $config, string $currentUrl)
    {
        $this->config = $config;
        $this->currentUrl = $currentUrl;
        $this->logger = $logger;
    }

    /**
     * @return array{filename: string, filesize: int|null, mimetype: string, sha1: string}|array{}
     */
    public function process(string $href)
    {
        $url = new Url($href, $this->currentUrl);

        return $this->config->urlToAssetArray($url);
    }
}
