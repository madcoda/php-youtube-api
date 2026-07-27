<?php

namespace Madcoda\Youtube\Tests\Unit;

use Madcoda\Youtube\Tests\Fake\ArrayHttpClient;
use Madcoda\Youtube\Tests\TestCase;
use Madcoda\Youtube\Youtube;

/**
 * Exercises the whole public API through an injected transport, asserting both
 * the request the library builds and what it does with the response. No network
 * and no API key.
 */
class ApiSurfaceTest extends TestCase
{
    /**
     * @var Youtube
     */
    protected $youtube;

    /**
     * @var ArrayHttpClient
     */
    protected $http;

    protected function set_up()
    {
        $this->youtube = new Youtube(array('key' => 'TESTKEY'));
        $this->http = new ArrayHttpClient();
        $this->youtube->setHttpClient($this->http);
    }

    protected function respondWith($fixture)
    {
        $this->http->queue($this->fixture($fixture));
    }

    // ---------------------------------------------------------------- videos

    public function testGetVideoInfo()
    {
        $this->respondWith('videos.single');
        $video = $this->youtube->getVideoInfo('rie-hPVJ7Sw');

        $this->assertSame('rie-hPVJ7Sw', $video->id);
        $this->assertSame('youtube#video', $video->kind);
        $this->assertObjectHasProperty('snippet', $video);
        $this->assertObjectHasProperty('statistics', $video);
        $this->assertObjectHasProperty('contentDetails', $video);
        $this->assertObjectHasProperty('status', $video);

        $this->assertSame('https://www.googleapis.com/youtube/v3/videos', $this->http->url());
        $this->assertSame('rie-hPVJ7Sw', $this->http->params()['id']);
    }

    public function testGetVideosInfoJoinsIdsWithCommas()
    {
        $this->respondWith('videos.multiple');
        $videos = $this->youtube->getVideosInfo(array('rie-hPVJ7Sw', 'lRRk97FYLJM'));

        $this->assertIsArray($videos);
        $this->assertCount(2, $videos);
        $this->assertSame('rie-hPVJ7Sw,lRRk97FYLJM', $this->http->params()['id']);
    }

    public function testGetVideosInfoAcceptsAPreJoinedString()
    {
        $this->respondWith('videos.multiple');
        $this->youtube->getVideosInfo('a,b');

        $this->assertSame('a,b', $this->http->params()['id']);
    }

    // ---------------------------------------------------------------- search

    public function testSearch()
    {
        $this->respondWith('search');
        $results = $this->youtube->search('Android', 3);

        $this->assertCount(3, $results);
        $this->assertSame('youtube#searchResult', $results[0]->kind);
        $this->assertSame('Android', $this->http->params()['q']);
        $this->assertSame(3, $this->http->params()['maxResults']);
    }

    public function testSearchVideosSetsTypeAndOrder()
    {
        $this->respondWith('search');
        $this->youtube->searchVideos('Android', 5, 'title');

        $params = $this->http->params();
        $this->assertSame('video', $params['type']);
        $this->assertSame('title', $params['order']);
        $this->assertSame(5, $params['maxResults']);
    }

    public function testSearchVideosOmitsOrderWhenNotGiven()
    {
        $this->respondWith('search');
        $this->youtube->searchVideos('Android', 5);

        $this->assertArrayNotHasKey('order', $this->http->params());
    }

    public function testSearchChannelVideosScopesToTheChannel()
    {
        $this->respondWith('search');
        $this->youtube->searchChannelVideos('Android', 'UCVHFbqXqoYvEWM1Ddxl0QDg', 4);

        $params = $this->http->params();
        $this->assertSame('UCVHFbqXqoYvEWM1Ddxl0QDg', $params['channelId']);
        $this->assertSame('video', $params['type']);
    }

    public function testSearchChannelLiveStreamSetsEventType()
    {
        $this->respondWith('search');
        $this->youtube->searchChannelLiveStream('news', 'UCR3asjvr_WAaxwJYEDV_Bfw', 2);

        $this->assertSame('live', $this->http->params()['eventType']);
    }

    public function testSearchAdvancedCanReturnPageInfo()
    {
        $this->respondWith('search');
        $response = $this->youtube->searchAdvanced(array('q' => 'Android'), true);

        $this->assertArrayHasKey('results', $response);
        $this->assertArrayHasKey('info', $response);
        $this->assertSame('youtube#searchListResponse', $response['info']['kind']);
        $this->assertSame('CAUQAA', $response['info']['nextPageToken']);
    }

    public function testSearchAdvancedRequiresAQuery()
    {
        $this->expectException('InvalidArgumentException');
        $this->youtube->searchAdvanced(array());
    }

    public function testSearchAdvancedReturnsFalseWhenNothingMatches()
    {
        $this->respondWith('search.empty');

        $this->assertFalse($this->youtube->searchAdvanced(array('q' => 'nothing-matches-this')));
    }

    public function testPaginateResultsPassesTheToken()
    {
        $this->respondWith('search.page2');
        $response = $this->youtube->paginateResults(array('q' => 'Android'), 'CAUQAA');

        $this->assertSame('CAUQAA', $this->http->params()['pageToken']);
        $this->assertSame('CAUQAQ', $response['info']['prevPageToken']);
    }

    public function testPaginateResultsOmitsTheTokenWhenNull()
    {
        $this->respondWith('search');
        $this->youtube->paginateResults(array('q' => 'Android'), null);

        $this->assertArrayNotHasKey('pageToken', $this->http->params());
    }

    // -------------------------------------------------------------- channels

    public function testGetChannelByName()
    {
        $this->respondWith('channels.single');
        $channel = $this->youtube->getChannelByName('Google');

        $this->assertSame('youtube#channel', $channel->kind);
        $this->assertSame('Google', $channel->snippet->title);
        $this->assertSame('Google', $this->http->params()['forUsername']);
    }

    public function testGetChannelByIdMergesOptionalParams()
    {
        $this->respondWith('channels.single');
        $this->youtube->getChannelById('UCK8sQmJBp8GCxrOtXWBpyEA', array('order' => 'title'));

        $params = $this->http->params();
        $this->assertSame('UCK8sQmJBp8GCxrOtXWBpyEA', $params['id']);
        $this->assertSame('title', $params['order']);
    }

    /**
     * $optionalParams defaults to false, and a truthy non-array reached
     * array_merge(), which is a TypeError on PHP 8.
     */
    public function testGetChannelByIdIgnoresNonArrayOptionalParams()
    {
        $this->respondWith('channels.single');
        $channel = $this->youtube->getChannelById('UCK8sQmJBp8GCxrOtXWBpyEA', true);

        $this->assertSame('youtube#channel', $channel->kind);
    }

    public function testGetChannelsByIdJoinsIds()
    {
        $this->respondWith('channels.multiple');
        $channels = $this->youtube->getChannelsById(
            array('UCk1SpWNzOs4MYmr0uICEntg', 'UCK8sQmJBp8GCxrOtXWBpyEA')
        );

        $this->assertCount(2, $channels);
        $this->assertSame(
            'UCk1SpWNzOs4MYmr0uICEntg,UCK8sQmJBp8GCxrOtXWBpyEA',
            $this->http->params()['id']
        );
    }

    public function testGetChannelsByIdIgnoresNonArrayOptionalParams()
    {
        $this->respondWith('channels.multiple');

        $this->assertCount(2, $this->youtube->getChannelsById(array('a', 'b'), true));
    }

    public function testGetChannelFromUrlWithAChannelPath()
    {
        $this->respondWith('channels.single');
        $channel = $this->youtube->getChannelFromURL(
            'https://www.youtube.com/channel/UCK8sQmJBp8GCxrOtXWBpyEA'
        );

        $this->assertSame('UCK8sQmJBp8GCxrOtXWBpyEA', $channel->id);
        $this->assertSame('UCK8sQmJBp8GCxrOtXWBpyEA', $this->http->params()['id']);
    }

    public function testGetChannelFromUrlWithAUserPath()
    {
        $this->respondWith('channels.single');
        $this->youtube->getChannelFromURL('http://www.youtube.com/user/Google');

        $this->assertSame('Google', $this->http->params()['forUsername']);
    }

    public function testGetChannelFromUrlRejectsANonYoutubeUrl()
    {
        $this->expectException('Exception');
        $this->youtube->getChannelFromURL('https://google.com');
    }

    public function testGetChannelFromUrlRejectsAnUnknownYoutubePath()
    {
        $this->expectException('Exception');
        $this->youtube->getChannelFromURL('https://www.youtube.com/invalid/UCyNF0DijeiZ8jhc_xlVa4Vg');
    }

    // ------------------------------------------------------------- playlists

    public function testGetPlaylistsByChannelId()
    {
        $this->respondWith('playlists');
        $playlists = $this->youtube->getPlaylistsByChannelId('UCK8sQmJBp8GCxrOtXWBpyEA');

        $this->assertCount(2, $playlists);
        $this->assertSame('youtube#playlist', $playlists[0]->kind);
        $this->assertSame('UCK8sQmJBp8GCxrOtXWBpyEA', $this->http->params()['channelId']);
    }

    public function testGetPlaylistById()
    {
        $this->respondWith('playlists');
        $playlist = $this->youtube->getPlaylistById('PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs');

        $this->assertSame('youtube#playlist', $playlist->kind);
        $this->assertSame('PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs', $this->http->params()['id']);
    }

    public function testGetPlaylistItemsByPlaylistId()
    {
        $this->respondWith('playlistItems');
        $items = $this->youtube->getPlaylistItemsByPlaylistId('PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs', 2);

        $this->assertCount(2, $items);
        $this->assertSame('youtube#playlistItem', $items[0]->kind);
        $this->assertSame(2, $this->http->params()['maxResults']);
    }

    public function testGetPlaylistItemsAdvancedCanReturnPageInfo()
    {
        $this->respondWith('playlistItems');
        $response = $this->youtube->getPlaylistItemsByPlaylistIdAdvanced(
            array('playlistId' => 'PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs'),
            true
        );

        $this->assertSame('youtube#playlistItemListResponse', $response['info']['kind']);
        $this->assertSame('CDIQAA', $response['info']['nextPageToken']);
    }

    public function testGetPlaylistItemsAdvancedRequiresAPlaylistId()
    {
        $this->expectException('InvalidArgumentException');
        $this->youtube->getPlaylistItemsByPlaylistIdAdvanced(null, true);
    }

    // ------------------------------------------------------------ activities

    public function testGetActivitiesByChannelId()
    {
        $this->respondWith('activities');
        $activities = $this->youtube->getActivitiesByChannelId('UCK8sQmJBp8GCxrOtXWBpyEA');

        $this->assertCount(1, $activities);
        $this->assertSame('youtube#activity', $activities[0]->kind);
    }

    public function testGetActivitiesRequiresAChannelId()
    {
        $this->expectException('InvalidArgumentException');
        $this->youtube->getActivitiesByChannelId('');
    }

    // ----------------------------------------------------------------- error

    public function testAnApiErrorBodyBecomesAnException()
    {
        $this->respondWith('error.quota');

        $this->expectException('Exception');
        $this->expectExceptionCode(403);
        $this->youtube->getVideoInfo('rie-hPVJ7Sw');
    }

    public function testATransportFailurePropagates()
    {
        $this->http->willThrow(new \Exception('Curl Error : Could not resolve host', 6));

        $this->expectException('Exception');
        $this->youtube->getVideoInfo('rie-hPVJ7Sw');
    }

    // -------------------------------------------------------- configuration

    public function testTheApiKeyIsSentOnEveryRequest()
    {
        $this->respondWith('videos.single');
        $this->youtube->getVideoInfo('x');

        $this->assertSame('TESTKEY', $this->http->params()['key']);
    }

    public function testSetApiKeySwitchesTheKey()
    {
        $this->youtube->setApiKey('SECONDKEY');
        $this->respondWith('videos.single');
        $this->youtube->getVideoInfo('x');

        $this->assertSame('SECONDKEY', $this->http->params()['key']);
    }

    public function testSetApisOverridesEndpoints()
    {
        $this->youtube->setAPIs(array('videos.list' => 'https://example.test/videos'));
        $this->respondWith('videos.single');
        $this->youtube->getVideoInfo('x');

        $this->assertSame('https://example.test/videos', $this->http->url());
    }

    public function testGetApiReturnsTheConfiguredEndpoint()
    {
        $this->assertSame(
            'https://www.googleapis.com/youtube/v3/search',
            $this->youtube->getApi('search.list')
        );
    }
}
