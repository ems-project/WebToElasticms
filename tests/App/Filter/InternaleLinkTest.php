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
        $config = new Config();
        $config->setHosts(['demo.com']);
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
}
