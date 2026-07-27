<?php

namespace Madcoda\Youtube\Tests\Integration;

use Madcoda\Youtube\Tests\TestCase;
use Madcoda\Youtube\Youtube;

/**
 * Tests that talk to the real Youtube Data API.
 *
 * These need a key in YOUTUBE_API_KEY and consume quota, so they are skipped by
 * default and excluded from the default test run. Run them with:
 *
 *     YOUTUBE_API_KEY=... vendor/bin/phpunit --group live
 *
 * Their purpose is to catch changes in the API's response shape. Assertions are
 * deliberately about structure rather than exact values: the old suite asserted
 * on live data such as a channel being titled "Google" and on an exact result
 * count, which broke whenever Google changed anything.
 *
 * @group live
 */
class LiveApiTest extends TestCase
{
    /**
     * @var Youtube
     */
    protected $youtube;

    protected function set_up()
    {
        $key = getenv('YOUTUBE_API_KEY');

        if (empty($key)) {
            $this->markTestSkipped('Set YOUTUBE_API_KEY to run the live API tests.');
        }

        $this->youtube = new Youtube(array('key' => $key));
    }

    public function testGetVideoInfoReturnsTheExpectedShape()
    {
        $video = $this->youtube->getVideoInfo('rie-hPVJ7Sw');

        $this->assertSame('rie-hPVJ7Sw', $video->id);
        $this->assertSame('youtube#video', $video->kind);
        $this->assertObjectHasProperty('snippet', $video);
        $this->assertObjectHasProperty('statistics', $video);
        $this->assertObjectHasProperty('contentDetails', $video);
        $this->assertObjectHasProperty('status', $video);
    }

    public function testGetVideosInfoReturnsEachRequestedVideo()
    {
        $ids = array('rie-hPVJ7Sw', 'lRRk97FYLJM');
        $videos = $this->youtube->getVideosInfo($ids);

        $this->assertIsArray($videos);

        foreach ($videos as $video) {
            $this->assertContains($video->id, $ids);
            $this->assertSame('youtube#video', $video->kind);
        }
    }

    public function testAnInvalidKeyThrows()
    {
        $youtube = new Youtube(array('key' => 'nonsense'));

        $this->expectException('Exception');
        $youtube->getVideoInfo('rie-hPVJ7Sw');
    }

    public function testSearchReturnsResults()
    {
        $results = $this->youtube->search('Android', 5);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        // The API treats maxResults as an upper bound, not a guarantee.
        $this->assertLessThanOrEqual(5, count($results));
        $this->assertSame('youtube#searchResult', $results[0]->kind);
    }

    public function testSearchVideosReturnsOnlyVideos()
    {
        $results = $this->youtube->searchVideos('Android', 5);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertSame('youtube#video', $result->id->kind);
        }
    }

    public function testSearchAdvancedReturnsPageInfo()
    {
        $response = $this->youtube->searchAdvanced(array(
            'q'          => 'Android',
            'type'       => 'video',
            'part'       => 'id, snippet',
            'maxResults' => 5,
        ), true);

        $this->assertSame('youtube#searchListResponse', $response['info']['kind']);
        $this->assertNotEmpty($response['results']);
    }

    public function testPaginateResultsWalksToTheNextPage()
    {
        $params = array(
            'q'          => 'Android',
            'type'       => 'video',
            'part'       => 'id, snippet',
            'maxResults' => 5,
        );

        $first = $this->youtube->searchAdvanced($params, true);
        $token = $first['info']['nextPageToken'];
        $this->assertNotEmpty($token, 'Expected a nextPageToken on the first page.');

        $second = $this->youtube->paginateResults($params, $token);
        $this->assertSame('youtube#searchListResponse', $second['info']['kind']);
        $this->assertNotEmpty($second['results']);
    }

    public function testGetChannelByIdReturnsTheExpectedShape()
    {
        $channelId = 'UCK8sQmJBp8GCxrOtXWBpyEA';
        $channel = $this->youtube->getChannelById($channelId);

        $this->assertSame('youtube#channel', $channel->kind);
        $this->assertSame($channelId, $channel->id);
        $this->assertObjectHasProperty('snippet', $channel);
        $this->assertObjectHasProperty('contentDetails', $channel);
        $this->assertObjectHasProperty('statistics', $channel);
    }

    public function testGetChannelsByIdReturnsEachChannel()
    {
        $ids = array('UCk1SpWNzOs4MYmr0uICEntg', 'UCK8sQmJBp8GCxrOtXWBpyEA');
        $channels = $this->youtube->getChannelsById($ids);

        $this->assertCount(2, $channels);
        foreach ($channels as $channel) {
            $this->assertContains($channel->id, $ids);
        }
    }

    public function testGetChannelFromUrlWithAChannelPath()
    {
        $channel = $this->youtube->getChannelFromURL(
            'https://www.youtube.com/channel/UCK8sQmJBp8GCxrOtXWBpyEA'
        );

        $this->assertSame('UCK8sQmJBp8GCxrOtXWBpyEA', $channel->id);
    }

    public function testGetPlaylistsByChannelId()
    {
        $playlists = $this->youtube->getPlaylistsByChannelId('UCK8sQmJBp8GCxrOtXWBpyEA');

        $this->assertNotEmpty($playlists);
        $this->assertSame('youtube#playlist', $playlists[0]->kind);
    }

    public function testGetPlaylistItemsByPlaylistId()
    {
        $playlists = $this->youtube->getPlaylistsByChannelId('UCK8sQmJBp8GCxrOtXWBpyEA');
        $this->assertNotEmpty($playlists);

        $items = $this->youtube->getPlaylistItemsByPlaylistId($playlists[0]->id);

        $this->assertNotEmpty($items);
        $this->assertSame('youtube#playlistItem', $items[0]->kind);
    }

    public function testGetActivitiesByChannelId()
    {
        $activities = $this->youtube->getActivitiesByChannelId('UCK8sQmJBp8GCxrOtXWBpyEA');

        $this->assertNotEmpty($activities);
        $this->assertSame('youtube#activity', $activities[0]->kind);
    }

    /**
     * A deleted or otherwise unavailable video yields an empty items list,
     * which decodeSingle() reports as false rather than throwing.
     */
    public function testAnUnavailableVideoReturnsFalse()
    {
        $this->assertFalse($this->youtube->getVideoInfo('Utn7NBtbHL4'));
    }
}
