<?php

declare(strict_types=1);

namespace App\Tests\Filter\Html;

use App\Config\Config;
use App\Filter\Html\StyleCleaner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class StyleCleanerTest extends TestCase
{
    public function testCleaning(): void
    {
        $config = new Config();
        $styleCleaner = new StyleCleaner($config);

        $crawler = new Crawler('<html><body><div style="padding: inherit;">foobar</div></body></html>');
        $html = $styleCleaner->process($crawler->filter('body'))->html();
        $this->assertEquals('<div>foobar</div>', $html);
    }
}
