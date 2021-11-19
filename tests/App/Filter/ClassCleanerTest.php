<?php

declare(strict_types=1);

namespace App\Tests\Filter\Html;

use App\Config\Config;
use App\Filter\Html\ClassCleaner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class ClassCleanerTest extends TestCase
{
    public function testClassCleaner(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getValidClasses')
            ->willReturn(['to-keep', 'top']);

        $internalLink = new ClassCleaner($config);
        $crawler = new Crawler(
'<div class="to-keep       top no get-away">foobar</div>');

        $internalLink->process($crawler->filter('body'));
        $this->assertEquals(
'<div class="to-keep top">foobar</div>', $crawler->filter('body')->html());
    }

    public function testClassCleanerNested(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getValidClasses')
            ->willReturn(['to-keep', 'top']);

        $internalLink = new ClassCleaner($config);
        $crawler = new Crawler(
            '<div class="to-keep       top no get-away">foobar <div class="to-keep       top no get-away">foobar</div></div>');

        $internalLink->process($crawler->filter('body'));
        $this->assertEquals(
            '<div class="to-keep top">foobar <div class="to-keep top">foobar</div></div>', $crawler->filter('body')->html());
    }
}
