<?php

declare(strict_types=1);

namespace App\Tests\Filter\Html;

use App\Config\Config;
use App\Filter\Html\InternalLink;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class InternaleLinkTest extends TestCase
{
    public function testInternalLink(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getHosts')
            ->willReturn(['demo.com']);
        $config->method('findInternalLink')
            ->willReturn('ems://object:page:ouuid');

        $internalLink = new InternalLink($config, 'https://demo.com/a/b');

        $crawler = new Crawler(
'<div style="padding: inherit;"><a href="https://demo.com/toto/link">Url</a></div>
<div style="padding: inherit;"><a href="//demo.com/toto/link">Url</a></div>
<div style="padding: inherit;"><a href="/toto/link">Absolute link</a></div>
<div style="padding: inherit;"><a href="../../toto/link">Absolute link</a></div>
<div style="padding: inherit;"><img src="../asset/images/test.png"></div>
<div style="padding: inherit;"><a href="https://www.google.com">Google</a></div>
<div style="padding: inherit;"><a href="//www.google.com">Google</a></div>');

        $internalLink->process($crawler->filter('body'));
        $this->assertEquals(
'<div style="padding: inherit;"><a href="ems://object:page:ouuid">Url</a></div>
<div style="padding: inherit;"><a href="ems://object:page:ouuid">Url</a></div>
<div style="padding: inherit;"><a href="ems://object:page:ouuid">Absolute link</a></div>
<div style="padding: inherit;"><a href="ems://object:page:ouuid">Absolute link</a></div>
<div style="padding: inherit;"><img src="ems://object:page:ouuid"></div>
<div style="padding: inherit;"><a href="https://www.google.com">Google</a></div>
<div style="padding: inherit;"><a href="//www.google.com">Google</a></div>', $crawler->filter('body')->html());
    }

    public function testLinkToClean(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getHosts')
            ->willReturn(['demo.com']);
        $config->method('findInternalLink')
            ->willReturn('ems://object:page:ouuid');
        $config->method('getLinkToClean')
            ->willReturn(["/^\/fr\/glossaire/"]);
        $crawler = new Crawler(
            '<div style="padding: inherit;"><a href="//demo.com/fr/glossaire?totot">Url</a></div>
<div style="padding: inherit;"><a href="../../fr/glossaire">link</a></div>
<div style="padding: inherit;"><a href="/fr/glossaire">link</a></div>
<div style="padding: inherit;"><a href="/autre">link</a> toto <a href="/fr/glossaire">link</a> totot</div>');
        $internalLink = new InternalLink($config, 'https://demo.com/a/b');

        $internalLink->process($crawler->filter('body'));
        $this->assertEquals('<div style="padding: inherit;">Url</div>
<div style="padding: inherit;">link</div>
<div style="padding: inherit;">link</div>
<div style="padding: inherit;"><a href="ems://object:page:ouuid">link</a> toto link totot</div>', $crawler->filter('body')->html());
    }
}
