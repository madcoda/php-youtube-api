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
class YoutubeQuotasPlaylistsListTest extends TestCase
{
    public function testPlaylistsListWithNoParams()
    {
        $this->assertEquals(
            1,
            YoutubeQuotas::create('playlists.list', '')->quotaUsed()
        );
    }

    public function testPlaylistsListWithSomeParams()
    {
        $this->assertEquals(
            5,
            YoutubeQuotas::create(
                'playlists.list',
                'id, player, snippet, status, player'
            )->quotaUsed()
        );
    }

    public function testPlaylistsListWithAllParams()
    {
        $this->assertEquals(
            7,
            YoutubeQuotas::create(
                'playlists.list',
                'id, player, snippet, status, contentDetails, player'
            )->quotaUsed()
        );
    }


}
