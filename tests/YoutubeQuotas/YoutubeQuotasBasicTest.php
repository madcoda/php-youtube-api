<?php

namespace Madcoda\Youtube\Tests;

use Madcoda\Youtube\YoutubeQuotas;

use PHPUnit\Framework\TestCase;

/**
 * Class YoutubeQuotasTest
 *
 * @category YoutubeQuotas
 * @package  YoutubeQuotas
 * @author   Frederick tyteca <frederick@tyteca.net>
 */
class YoutubeQuotasBasicTest extends TestCase
{
    public function testingPartParams()
    {
        $expectedParams = ['id', 'snippet'];
        $this->assertEqualsCanonicalizing(
            $expectedParams,
            YoutubeQuotas::create('channels.list', 'id,snippet')->partParams()
        );
    }

    public function testingMorePartParamsWithSpaceAndDuplicates()
    {
        $expectedParams = ['id', 'snippet', 'status', 'contentDetails'];
        $this->assertEqualsCanonicalizing(
            $expectedParams,
            YoutubeQuotas::create('channels.list', 'id,snippet, status,status,contentDetails')->partParams()
        );
    }

    public function testSearchList()
    {
        $this->assertEquals(
            100,
            YoutubeQuotas::create('search.list')->quotaUsed()
        );
    }

    public function testMakingMoreQuery()
    {
        $quotaObj = YoutubeQuotas::create('search.list');
        $this->assertEquals( 100, $quotaObj->quotaUsed() );
        
        $quotaObj->addQuery('channels.list', 'snippet,unicorn,poney,status,contentDetails');
        $this->assertEquals( 107, $quotaObj->quotaUsed());

        $quotaObj->addQuery('search.list');
        $this->assertEquals( 207, $quotaObj->quotaUsed());

        $quotaObj->addQuery('playlistItems.list', 'status, contentDetails');
        $this->assertEquals( 212, $quotaObj->quotaUsed());
    }
}
