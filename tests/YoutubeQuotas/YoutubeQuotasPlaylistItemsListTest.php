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
class YoutubeQuotasPlaylistItemsListTest extends TestCase
{
    public function testPlaylistItemsListWithNoParams()
    {
        $this->assertEquals(
            1,
            YoutubeQuotas::create('playlistItems.list', '')->quotaUsed()
        );
    }

    public function testPlaylistItemsListWithSomeParams()
    {
        $this->assertEquals(
            5,
            YoutubeQuotas::create(
                'playlistItems.list',
                'status, contentDetails'
            )->quotaUsed()
        );
    }

    public function testPlaylistItemsListWithAllParams()
    {
        $this->assertEquals(
            7,
            YoutubeQuotas::create(
                'playlistItems.list',
                'id, snippet, status, contentDetails'
            )->quotaUsed()
        );
    }


}
