<?php

declare(strict_types=1);

/**
 * This is an example plugin for Textpattern CMS.
 *
 * @link https://textpattern.com/
 */

namespace Rah\Sitemap\Test\Unit\Record;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey\Functions;
use Brain\Monkey;

final class ArticleRecordTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private \Rah_Sitemap_Record_ArticleRecord $subject;

    protected function setUp(): void
    {
        $this->subject = new \Rah_Sitemap_Record_ArticleRecord();
    }

    public function testGetName(): void
    {
        $this->assertTrue(true);

        $this->assertIsString($this->subject->getName());
    }

    public function testGetPagesOnlyOnePage(): void
    {
        Functions\stubs([
            'safe_count' => 11,
            'do_list' => [],
            'get_pref' => '',
            'getThings' => [],
            'safe_pfx' => '',
            'getCustomFields' => [],
        ]);

        $this->assertSame(1, $this->subject->getPages());
    }

    public function testGetPagesTwoPages(): void
    {
        Functions\stubs([
            'safe_count' => 52000,
            'do_list' => [],
            'get_pref' => '',
            'getThings' => [],
            'safe_pfx' => '',
            'getCustomFields' => [],
        ]);

        $this->assertSame(2, $this->subject->getPages());
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
    }
}
