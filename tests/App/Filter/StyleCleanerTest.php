<?php

declare(strict_types=1);

namespace App\Tests\Filter\Html;

use App\Config\ConfigManager;
use App\Filter\Html\StyleCleaner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class StyleCleanerTest extends TestCase
{
    public function testCleaning(): void
    {
        $config = new ConfigManager();
        $styleCleaner = new StyleCleaner($config);

        $crawler = new Crawler('<html><body><div style="padding: inherit;">foobar</div></body></html>');
        $styleCleaner->process($crawler->filter('body'));
        $this->assertEquals('<div>foobar</div>', $crawler->filter('body')->html());
        $this->assertEquals('<body><div>foobar</div></body>', $crawler->html());
    }

    public function testCleaningWithManyStyles(): void
    {
        $config = new ConfigManager();
        $styleCleaner = new StyleCleaner($config);

        $crawler = new Crawler('<div class="foobar" style="padding: inherit;">foobar</div><div style="padding: inherit;">foobar<div style="padding: inherit;">foobar</div></div>');
        $styleCleaner->process($crawler);
        $this->assertEquals('<div class="foobar">foobar</div><div>foobar<div>foobar</div></div>', $crawler->filter('body')->html());
    }
}
