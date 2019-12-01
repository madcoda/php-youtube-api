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
class YoutubeQuotasVideosListTest extends TestCase
{
    public function testVideosListWithNoParams()
    {
        $this->assertEquals(
            1,
            YoutubeQuotas::create('videos.list', '')->quotaUsed()
        );
    }

    public function testVideosListWithSomeParams()
    {
        $this->assertEquals(
            13,
            YoutubeQuotas::create(
                'videos.list',
                'snippet, contentDetails, status, statistics, recordingDetails, fileDetails, suggestions'
            )->quotaUsed()
        );
    }

    public function testVideosListWithAllParams()
    {
        $this->assertEquals(
            16,
            YoutubeQuotas::create(
                'videos.list',
                'snippet, contentDetails, status, statistics, player, topicDetails, recordingDetails, fileDetails, processingDetails, suggestions'
            )->quotaUsed()
        );
    }
}
