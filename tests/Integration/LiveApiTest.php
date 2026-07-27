<?php

namespace Madcoda\Youtube\Tests\Integration;

use Madcoda\Youtube\Tests\Subjects;
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
 * Their purpose is to catch changes in the API's behaviour. Assertions are
 * about structure rather than exact values: the old suite asserted on live data
 * such as a channel being titled "Google" and on an exact result count, which
 * broke whenever Google changed anything.
 *
 * The subjects live in Subjects, and all of them are institutional or
 * historically significant so they are unlikely to disappear.
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
        $video = $this->youtube->getVideoInfo(Subjects::VIDEO);

        $this->assertSame(Subjects::VIDEO, $video->id);
        $this->assertSame('youtube#video', $video->kind);
        $this->assertObjectHasProperty('snippet', $video);
        $this->assertObjectHasProperty('statistics', $video);
        $this->assertObjectHasProperty('contentDetails', $video);
        $this->assertObjectHasProperty('status', $video);
    }

    public function testGetVideosInfoReturnsEachRequestedVideo()
    {
        $ids = array(Subjects::VIDEO, Subjects::VIDEO_SECOND);
        $videos = $this->youtube->getVideosInfo($ids);

        $this->assertIsArray($videos);
        $this->assertCount(2, $videos);

        foreach ($videos as $video) {
            $this->assertContains($video->id, $ids);
            $this->assertSame('youtube#video', $video->kind);
        }
    }

    /**
     * A removed video yields a well-formed response with an empty items list,
     * which decodeSingle() reports as false rather than throwing.
     */
    public function testAnUnavailableVideoReturnsFalse()
    {
        $this->assertFalse($this->youtube->getVideoInfo(Subjects::VIDEO_UNAVAILABLE));
    }

    public function testAnInvalidKeyThrows()
    {
        $youtube = new Youtube(array('key' => 'nonsense'));

        $this->expectException('Exception');
        $youtube->getVideoInfo(Subjects::VIDEO);
    }

    public function testAnInvalidKeyReportsGooglesErrorCode()
    {
        $youtube = new Youtube(array('key' => 'nonsense'));

        try {
            $youtube->getVideoInfo(Subjects::VIDEO);
            $this->fail('Expected an exception for an invalid API key.');
        } catch (\Exception $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertStringContainsString('Error 400', $e->getMessage());
        }
    }

    public function testSearchReturnsResults()
    {
        $results = $this->youtube->search(Subjects::SEARCH_QUERY, 5);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        // The API treats maxResults as an upper bound, not a guarantee.
        $this->assertLessThanOrEqual(5, count($results));
        $this->assertSame('youtube#searchResult', $results[0]->kind);
    }

    public function testSearchVideosReturnsOnlyVideos()
    {
        $results = $this->youtube->searchVideos(Subjects::SEARCH_QUERY, 5);

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertSame('youtube#video', $result->id->kind);
        }
    }

    public function testSearchChannelVideosStaysWithinTheChannel()
    {
        $results = $this->youtube->searchChannelVideos(
            Subjects::SEARCH_QUERY,
            Subjects::CHANNEL_SECOND,
            5
        );

        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertSame(Subjects::CHANNEL_SECOND, $result->snippet->channelId);
        }
    }

    public function testSearchAdvancedReturnsPageInfo()
    {
        $response = $this->youtube->searchAdvanced(array(
            'q'          => Subjects::SEARCH_QUERY,
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
            'q'          => Subjects::SEARCH_QUERY,
            'type'       => 'video',
            'channelId'  => Subjects::CHANNEL_SECOND,
            'part'       => 'id, snippet',
            'maxResults' => 5,
        );

        $first = $this->youtube->searchAdvanced($params, true);
        $token = $first['info']['nextPageToken'];
        $this->assertNotEmpty($token, 'Expected a nextPageToken on the first page.');

        $second = $this->youtube->paginateResults($params, $token);
        $this->assertSame('youtube#searchListResponse', $second['info']['kind']);
        $this->assertNotEmpty($second['results']);

        // The second page must be different content, and must know its way back.
        $this->assertNotEmpty($second['info']['prevPageToken']);
        $this->assertNotSame(
            $first['results'][0]->id->videoId,
            $second['results'][0]->id->videoId
        );
    }

    public function testSearchWithNoMatchesReturnsFalse()
    {
        $results = $this->youtube->searchVideos(Subjects::SEARCH_QUERY_NO_RESULTS, 5);

        $this->assertFalse($results);
    }

    public function testGetChannelByIdReturnsTheExpectedShape()
    {
        $channel = $this->youtube->getChannelById(Subjects::CHANNEL);

        $this->assertSame('youtube#channel', $channel->kind);
        $this->assertSame(Subjects::CHANNEL, $channel->id);
        $this->assertObjectHasProperty('snippet', $channel);
        $this->assertObjectHasProperty('contentDetails', $channel);
        $this->assertObjectHasProperty('statistics', $channel);
    }

    public function testGetChannelsByIdReturnsEachChannel()
    {
        $ids = array(Subjects::CHANNEL, Subjects::CHANNEL_SECOND);
        $channels = $this->youtube->getChannelsById($ids);

        $this->assertCount(2, $channels);
        foreach ($channels as $channel) {
            $this->assertContains($channel->id, $ids);
        }
    }

    public function testGetChannelFromUrlWithAChannelPath()
    {
        $channel = $this->youtube->getChannelFromURL(
            'https://www.youtube.com/channel/' . Subjects::CHANNEL
        );

        $this->assertSame(Subjects::CHANNEL, $channel->id);
    }

    /**
     * The uploads playlist is derived from the channel id and exists for as
     * long as the channel does, which is what makes it a safe live subject.
     */
    public function testTheUploadsPlaylistMatchesTheChannel()
    {
        $channel = $this->youtube->getChannelById(Subjects::CHANNEL_SECOND);

        $this->assertSame(
            Subjects::PLAYLIST,
            $channel->contentDetails->relatedPlaylists->uploads
        );
    }

    public function testGetPlaylistsByChannelId()
    {
        $playlists = $this->youtube->getPlaylistsByChannelId(
            Subjects::CHANNEL_SECOND,
            array('maxResults' => 3)
        );

        $this->assertNotEmpty($playlists);
        $this->assertSame('youtube#playlist', $playlists[0]->kind);
        $this->assertSame(Subjects::CHANNEL_SECOND, $playlists[0]->snippet->channelId);
    }

    public function testGetPlaylistItemsByPlaylistId()
    {
        $items = $this->youtube->getPlaylistItemsByPlaylistId(Subjects::PLAYLIST, 3);

        $this->assertNotEmpty($items);
        $this->assertSame('youtube#playlistItem', $items[0]->kind);
        $this->assertSame(Subjects::PLAYLIST, $items[0]->snippet->playlistId);
    }

    public function testGetActivitiesByChannelId()
    {
        $activities = $this->youtube->getActivitiesByChannelId(
            Subjects::CHANNEL_SECOND,
            array('maxResults' => 3)
        );

        $this->assertNotEmpty($activities);
        $this->assertSame('youtube#activity', $activities[0]->kind);
        $this->assertSame(Subjects::CHANNEL_SECOND, $activities[0]->snippet->channelId);
    }
}
