<?php

namespace Madcoda\Youtube\Tests;

use Madcoda\Youtube\Youtube;
use Madcoda\Youtube\YoutubeQuotas;

use PHPUnit\Framework\TestCase;

/**
 * Class YoutubeQuotasTest
 *
 * @category YoutubeQuotas
 * @package  YoutubeQuotas
 * @author   Frederick tyteca <frederick@tyteca.net>
 */
class YoutubeQuotasInjectionTest extends TestCase
{
    // !!!!! DO NOT USE THIS API KEY FOR PRODUCTION USE !!!!! */
    const _TEST_API_KEY = 'AIzaSyDlNBnbhP7G9z_8qunELCJ8012PP3t_c1o';
    // !!!!! THIS KEY WOULD BE REVOKED BY AUTHOR ANYTIME !!!!! */

    protected $youtube;

    public function setUp(): void
    {
        $this->youtube = new Youtube(['key' => self::_TEST_API_KEY]);
        $this->youtube->injectQuotaCalculator(new YoutubeQuotas());
    }

    public function tearDown(): void
    {
        $this->youtube = null;
        $this->optionParams = null;
    }

    public function testGetVideoInfoWithInjection()
    {
        $this->youtube->getVideoInfo($videoId = 'rie-hPVJ7Sw');
        $this->assertEquals(
            9,
            $this->youtube->quotaObj->quotaUsed()
        );
    }

    public function testGetChannelByIdWithInjection()
    {
        $this->youtube->getChannelById($channelId = 'UCk1SpWNzOs4MYmr0uICEntg');
        $this->assertEquals(
            9,
            $this->youtube->quotaObj->quotaUsed()
        );
    }

    public function testGetChannelByNameWithInjection()
    {
        $this->youtube->getChannelByName('Google');
        $this->assertEquals(
            9,
            $this->youtube->quotaObj->quotaUsed()
        );
    }

    public function testSearchAdvancedIdWithInjection()
    {
        $limit = rand(3, 10);
        $this->youtube->searchVideos('Android', $limit, 'title');
        $this->assertEquals(
            100,
            $this->youtube->quotaObj->quotaUsed()
        );
    }

    public function testGetPlaylistsByChannelIdWithInjection()
    {
        $this->youtube->getPlaylistsByChannelId('UCK8sQmJBp8GCxrOtXWBpyEA');
        $this->assertEquals(
            5,
            $this->youtube->quotaObj->quotaUsed()
        );
    }

    public function testGetPlaylistItemsByPlaylistIdWithInjection()
    {
        $this->youtube->getPlaylistItemsByPlaylistId('PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs');
        $this->assertEquals(
            7,
            $this->youtube->quotaObj->quotaUsed()
        );
    }
}
