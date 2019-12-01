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
class YoutubeQuotasChannelsListTest extends TestCase
{
    public function testChannelsListWithNoParams()
    {
        $this->assertEquals(
            1,
            YoutubeQuotas::create('channels.list', '')->quotaUsed()
        );
    }
    
    public function testChannelsListWithSomeParams ()
    {
        $this->assertEquals(
            7,
            YoutubeQuotas::create('channels.list', 'id,snippet,status,contentDetails')->quotaUsed()
        );
    }

    public function testChannelsListWithSomeFoolishParams ()
    {
        $quotaObj = YoutubeQuotas::create('channels.list', 'snippet,unicorn,poney,status,contentDetails');
        $this->assertEquals(
            7,
            $quotaObj->quotaUsed()
        );
        
        $this->assertEqualsCanonicalizing(
            ['snippet', 'status', 'contentDetails'],
            $quotaObj->partParams()
        );
    }
}
