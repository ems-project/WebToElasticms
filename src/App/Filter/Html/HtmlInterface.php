<?php

namespace App\Filter\Html;

use App\Config\WebResource;
use Symfony\Component\DomCrawler\Crawler;

interface HtmlInterface
{
    public function process(WebResource $resource, Crawler $content): void;
}
