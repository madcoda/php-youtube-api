<?php

namespace Madcoda\Youtube\Tests\Unit;

use Madcoda\Youtube\Tests\Fake\ArrayHttpClient;
use Madcoda\Youtube\Tests\Subjects;
use Madcoda\Youtube\Tests\TestCase;
use Madcoda\Youtube\Youtube;

/**
 * Exercises the whole public API through an injected transport, asserting both
 * the request the library builds and what it does with the response. No network
 * and no API key.
 *
 * Values that originate at Google are asserted against the fixture's own
 * contents so that re-recording does not break the suite. Values the library
 * produces -- endpoints, parameter names, the injected key -- are asserted as
 * literals, since those are the thing under test.
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
        $raw = $this->fixtureData('videos.single');

        $video = $this->youtube->getVideoInfo(Subjects::VIDEO);

        $this->assertSame(Subjects::VIDEO, $video->id);
        $this->assertSame('youtube#video', $video->kind);
        $this->assertSame($raw->items[0]->snippet->title, $video->snippet->title);
        $this->assertObjectHasProperty('snippet', $video);
        $this->assertObjectHasProperty('statistics', $video);
        $this->assertObjectHasProperty('contentDetails', $video);
        $this->assertObjectHasProperty('status', $video);

        $this->assertSame('https://www.googleapis.com/youtube/v3/videos', $this->http->url());
        $this->assertSame(Subjects::VIDEO, $this->http->params()['id']);
    }

    public function testGetVideoInfoReturnsFalseWhenTheVideoIsGone()
    {
        $this->respondWith('videos.empty');

        $this->assertFalse($this->youtube->getVideoInfo(Subjects::VIDEO_UNAVAILABLE));
    }

    public function testGetVideosInfoJoinsIdsWithCommas()
    {
        $this->respondWith('videos.multiple');
        $raw = $this->fixtureData('videos.multiple');

        $videos = $this->youtube->getVideosInfo(array(Subjects::VIDEO, Subjects::VIDEO_SECOND));

        $this->assertIsArray($videos);
        $this->assertCount(count($raw->items), $videos);
        $this->assertSame(
            Subjects::VIDEO . ',' . Subjects::VIDEO_SECOND,
            $this->http->params()['id']
        );
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
        $raw = $this->fixtureData('search');

        $results = $this->youtube->search(Subjects::SEARCH_QUERY, 3);

        $this->assertCount(count($raw->items), $results);
        $this->assertSame('youtube#searchResult', $results[0]->kind);
        $this->assertSame(Subjects::SEARCH_QUERY, $this->http->params()['q']);
        $this->assertSame(3, $this->http->params()['maxResults']);
    }

    public function testSearchVideosSetsTypeAndOrder()
    {
        $this->respondWith('search');
        $this->youtube->searchVideos(Subjects::SEARCH_QUERY, 5, 'title');

        $params = $this->http->params();
        $this->assertSame('video', $params['type']);
        $this->assertSame('title', $params['order']);
        $this->assertSame(5, $params['maxResults']);
    }

    public function testSearchVideosOmitsOrderWhenNotGiven()
    {
        $this->respondWith('search');
        $this->youtube->searchVideos(Subjects::SEARCH_QUERY, 5);

        $this->assertArrayNotHasKey('order', $this->http->params());
    }

    public function testSearchChannelVideosScopesToTheChannel()
    {
        $this->respondWith('search');
        $this->youtube->searchChannelVideos(Subjects::SEARCH_QUERY, Subjects::CHANNEL_SECOND, 4);

        $params = $this->http->params();
        $this->assertSame(Subjects::CHANNEL_SECOND, $params['channelId']);
        $this->assertSame('video', $params['type']);
    }

    public function testSearchChannelLiveStreamSetsEventType()
    {
        $this->respondWith('search');
        $this->youtube->searchChannelLiveStream('news', Subjects::CHANNEL_SECOND, 2);

        $this->assertSame('live', $this->http->params()['eventType']);
    }

    public function testSearchAdvancedCanReturnPageInfo()
    {
        $this->respondWith('search');
        $raw = $this->fixtureData('search');

        $response = $this->youtube->searchAdvanced(array('q' => Subjects::SEARCH_QUERY), true);

        $this->assertArrayHasKey('results', $response);
        $this->assertArrayHasKey('info', $response);
        $this->assertSame($raw->kind, $response['info']['kind']);
        $this->assertSame($raw->nextPageToken, $response['info']['nextPageToken']);
    }

    public function testSearchAdvancedRequiresAQuery()
    {
        $this->expectException('InvalidArgumentException');
        $this->youtube->searchAdvanced(array());
    }

    public function testSearchAdvancedReturnsFalseWhenNothingMatches()
    {
        $this->respondWith('search.empty');

        $this->assertFalse(
            $this->youtube->searchAdvanced(array('q' => Subjects::SEARCH_QUERY_NO_RESULTS))
        );
    }

    public function testPaginateResultsPassesTheToken()
    {
        $this->respondWith('search.page2');
        $raw = $this->fixtureData('search.page2');

        $response = $this->youtube->paginateResults(array('q' => Subjects::SEARCH_QUERY), 'CAMQAA');

        $this->assertSame('CAMQAA', $this->http->params()['pageToken']);
        $this->assertSame($raw->prevPageToken, $response['info']['prevPageToken']);
    }

    public function testPaginateResultsOmitsTheTokenWhenNull()
    {
        $this->respondWith('search');
        $this->youtube->paginateResults(array('q' => Subjects::SEARCH_QUERY), null);

        $this->assertArrayNotHasKey('pageToken', $this->http->params());
    }

    // -------------------------------------------------------------- channels

    public function testGetChannelByNameUsesForUsername()
    {
        $this->respondWith('channels.single');
        $this->youtube->getChannelByName('SomeLegacyUsername');

        $this->assertSame('SomeLegacyUsername', $this->http->params()['forUsername']);
    }

    public function testGetChannelById()
    {
        $this->respondWith('channels.single');
        $raw = $this->fixtureData('channels.single');

        $channel = $this->youtube->getChannelById(Subjects::CHANNEL);

        $this->assertSame('youtube#channel', $channel->kind);
        $this->assertSame(Subjects::CHANNEL, $channel->id);
        $this->assertSame($raw->items[0]->snippet->title, $channel->snippet->title);
        $this->assertSame(Subjects::CHANNEL, $this->http->params()['id']);
    }

    public function testGetChannelByIdMergesOptionalParams()
    {
        $this->respondWith('channels.single');
        $this->youtube->getChannelById(Subjects::CHANNEL, array('order' => 'title'));

        $this->assertSame('title', $this->http->params()['order']);
    }

    /**
     * $optionalParams defaults to false, and a truthy non-array reached
     * array_merge(), which is a TypeError on PHP 8.
     */
    public function testGetChannelByIdIgnoresNonArrayOptionalParams()
    {
        $this->respondWith('channels.single');
        $channel = $this->youtube->getChannelById(Subjects::CHANNEL, true);

        $this->assertSame('youtube#channel', $channel->kind);
    }

    public function testGetChannelsByIdJoinsIds()
    {
        $this->respondWith('channels.multiple');
        $raw = $this->fixtureData('channels.multiple');

        $channels = $this->youtube->getChannelsById(
            array(Subjects::CHANNEL, Subjects::CHANNEL_SECOND)
        );

        $this->assertCount(count($raw->items), $channels);
        $this->assertSame(
            Subjects::CHANNEL . ',' . Subjects::CHANNEL_SECOND,
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
            'https://www.youtube.com/channel/' . Subjects::CHANNEL
        );

        $this->assertSame(Subjects::CHANNEL, $channel->id);
        $this->assertSame(Subjects::CHANNEL, $this->http->params()['id']);
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
        $this->youtube->getChannelFromURL('https://www.youtube.com/invalid/' . Subjects::CHANNEL);
    }

    /**
     * Youtube replaced legacy usernames with @handles, and getChannelFromURL()
     * has no branch for them: a handle URL is neither /channel nor /user, so it
     * is rejected. Documented here because it is a real gap rather than
     * intended behaviour, and supporting it is a feature, not a bug fix.
     */
    public function testGetChannelFromUrlDoesNotYetSupportHandles()
    {
        $this->expectException('Exception');
        $this->youtube->getChannelFromURL('https://www.youtube.com/' . Subjects::CHANNEL_HANDLE);
    }

    // ------------------------------------------------------------- playlists

    public function testGetPlaylistsByChannelId()
    {
        $this->respondWith('playlists');
        $raw = $this->fixtureData('playlists');

        $playlists = $this->youtube->getPlaylistsByChannelId(Subjects::CHANNEL_SECOND);

        $this->assertCount(count($raw->items), $playlists);
        $this->assertSame('youtube#playlist', $playlists[0]->kind);
        $this->assertSame(Subjects::CHANNEL_SECOND, $this->http->params()['channelId']);
    }

    public function testGetPlaylistsByChannelIdMergesOptionalParams()
    {
        $this->respondWith('playlists');
        $this->youtube->getPlaylistsByChannelId(
            Subjects::CHANNEL_SECOND,
            array('maxResults' => 3)
        );

        $params = $this->http->params();
        $this->assertSame('https://www.googleapis.com/youtube/v3/playlists', $this->http->url());
        $this->assertSame(Subjects::CHANNEL_SECOND, $params['channelId']);
        $this->assertSame('id, snippet, status', $params['part']);
        $this->assertSame(3, $params['maxResults']);
    }

    public function testGetPlaylistsByChannelIdIgnoresNonArrayOptionalParams()
    {
        $this->respondWith('playlists');
        $playlists = $this->youtube->getPlaylistsByChannelId(Subjects::CHANNEL_SECOND, true);

        $this->assertSame('youtube#playlist', $playlists[0]->kind);
    }

    public function testGetPlaylistsByChannelIdAdvancedReturnsAPlainListByDefault()
    {
        $this->respondWith('playlists');
        $raw = $this->fixtureData('playlists');

        $playlists = $this->youtube->getPlaylistsByChannelIdAdvanced(array(
            'channelId' => Subjects::CHANNEL_SECOND,
            'part' => 'id, snippet, status'
        ));

        $this->assertIsArray($playlists);
        $this->assertArrayNotHasKey('results', $playlists);
        $this->assertCount(count($raw->items), $playlists);
        $this->assertSame($raw->items[0]->id, $playlists[0]->id);
    }

    public function testGetPlaylistsByChannelIdAdvancedCanReturnPageInfo()
    {
        $this->respondWith('playlists');
        $raw = $this->fixtureData('playlists');

        $response = $this->youtube->getPlaylistsByChannelIdAdvanced(
            array('channelId' => Subjects::CHANNEL_SECOND),
            true
        );

        $this->assertArrayHasKey('results', $response);
        $this->assertArrayHasKey('info', $response);
        $this->assertCount(count($raw->items), $response['results']);
        $this->assertSame($raw->kind, $response['info']['kind']);
        $this->assertSame($raw->pageInfo->totalResults, $response['info']['totalResults']);
        $this->assertSame($raw->nextPageToken, $response['info']['nextPageToken']);
    }

    public function testGetPlaylistsByChannelIdAdvancedRequiresAChannelId()
    {
        $this->expectException('InvalidArgumentException');
        $this->youtube->getPlaylistsByChannelIdAdvanced(array('part' => 'id, snippet'), true);
    }

    public function testGetPlaylistsByChannelIdAdvancedRejectsEmptyParams()
    {
        $this->expectException('InvalidArgumentException');
        $this->youtube->getPlaylistsByChannelIdAdvanced(null, true);
    }

    /**
     * The reason the Advanced method exists: a channel with more playlists than
     * one page can hold was silently truncated, because the caller had no way to
     * reach nextPageToken.
     */
    public function testChannelPlaylistsCanBeWalkedPageByPage()
    {
        $this->respondWith('playlists');
        $this->respondWith('playlists.page2');
        $page1Raw = $this->fixtureData('playlists');
        $page2Raw = $this->fixtureData('playlists.page2');

        $params = array(
            'channelId' => Subjects::CHANNEL_SECOND,
            'part' => 'id, snippet, status',
            'maxResults' => 3
        );

        $page1 = $this->youtube->getPlaylistsByChannelIdAdvanced($params, true);
        $this->assertArrayNotHasKey('pageToken', $this->http->params());
        $this->assertNotNull($page1['info']['nextPageToken']);

        $params['pageToken'] = $page1['info']['nextPageToken'];
        $page2 = $this->youtube->getPlaylistsByChannelIdAdvanced($params, true);

        $this->assertSame($page1Raw->nextPageToken, $this->http->params()['pageToken']);
        $this->assertSame($page2Raw->prevPageToken, $page2['info']['prevPageToken']);
        $this->assertSame(2, $this->http->callCount());

        $ids = array();
        foreach (array_merge($page1['results'], $page2['results']) as $playlist) {
            $ids[] = $playlist->id;
        }
        $this->assertCount(
            count($page1Raw->items) + count($page2Raw->items),
            array_unique($ids)
        );
    }

    public function testGetPlaylistById()
    {
        $this->respondWith('playlists');
        $playlist = $this->youtube->getPlaylistById(Subjects::PLAYLIST);

        $this->assertSame('youtube#playlist', $playlist->kind);
        $this->assertSame(Subjects::PLAYLIST, $this->http->params()['id']);
    }

    public function testGetPlaylistItemsByPlaylistId()
    {
        $this->respondWith('playlistItems');
        $raw = $this->fixtureData('playlistItems');

        $items = $this->youtube->getPlaylistItemsByPlaylistId(Subjects::PLAYLIST, 3);

        $this->assertCount(count($raw->items), $items);
        $this->assertSame('youtube#playlistItem', $items[0]->kind);
        $this->assertSame(3, $this->http->params()['maxResults']);
        $this->assertSame(Subjects::PLAYLIST, $this->http->params()['playlistId']);
    }

    public function testGetPlaylistItemsAdvancedCanReturnPageInfo()
    {
        $this->respondWith('playlistItems');
        $raw = $this->fixtureData('playlistItems');

        $response = $this->youtube->getPlaylistItemsByPlaylistIdAdvanced(
            array('playlistId' => Subjects::PLAYLIST),
            true
        );

        $this->assertSame($raw->kind, $response['info']['kind']);
        $this->assertSame($raw->nextPageToken, $response['info']['nextPageToken']);
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
        $raw = $this->fixtureData('activities');

        $activities = $this->youtube->getActivitiesByChannelId(Subjects::CHANNEL_SECOND);

        $this->assertCount(count($raw->items), $activities);
        $this->assertSame('youtube#activity', $activities[0]->kind);
        $this->assertSame(Subjects::CHANNEL_SECOND, $this->http->params()['channelId']);
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
        $this->youtube->getVideoInfo(Subjects::VIDEO);
    }

    public function testAnInvalidKeyErrorBecomesAnException()
    {
        $this->respondWith('error.badkey');

        $this->expectException('Exception');
        $this->expectExceptionCode(400);
        $this->youtube->getVideoInfo(Subjects::VIDEO);
    }

    public function testATransportFailurePropagates()
    {
        $this->http->willThrow(new \Exception('Curl Error : Could not resolve host', 6));

        $this->expectException('Exception');
        $this->youtube->getVideoInfo(Subjects::VIDEO);
    }

    // -------------------------------------------------------- configuration

    public function testTheApiKeyIsSentOnEveryRequest()
    {
        $this->respondWith('videos.single');
        $this->youtube->getVideoInfo(Subjects::VIDEO);

        $this->assertSame('TESTKEY', $this->http->params()['key']);
    }

    public function testSetApiKeySwitchesTheKey()
    {
        $this->youtube->setApiKey('SECONDKEY');
        $this->respondWith('videos.single');
        $this->youtube->getVideoInfo(Subjects::VIDEO);

        $this->assertSame('SECONDKEY', $this->http->params()['key']);
    }

    public function testSetApisOverridesEndpoints()
    {
        $this->youtube->setAPIs(array('videos.list' => 'https://example.test/videos'));
        $this->respondWith('videos.single');
        $this->youtube->getVideoInfo(Subjects::VIDEO);

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
