<?php

declare(strict_types=1);

namespace App\Tests\Filter\Html;

use App\Config\ConfigManager;
use App\Filter\Html\Striptag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class StriptagTest extends TestCase
{
    public function testStriptag(): void
    {
        $config = new ConfigManager();
        $styleCleaner = new Striptag($config);

        $crawler = new Crawler('<html><body><div style="padding: inherit;">foobar &egrave; &euro;</div></body></html>');
        $styleCleaner->process($crawler->filter('body'));
        $this->assertEquals('foobar è €', $crawler->filter('body')->text());
        $this->assertEquals('foobar è €', $crawler->text());
    }

    public function testStriptagWithManyStyles(): void
    {
        $config = new ConfigManager();
        $styleCleaner = new Striptag($config);

        $crawler = new Crawler('<div class="foobar" style="padding: inherit;">foobar &egrave; &euro;</div><div style="padding: inherit;">foobar<div style="padding: inherit;">foobar</div></div>');
        $styleCleaner->process($crawler);
        $this->assertEquals('foobar è €foobarfoobar', $crawler->filter('body')->text());
    }
}
