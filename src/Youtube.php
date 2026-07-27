<?php

namespace Madcoda\Youtube;

use Madcoda\Youtube\Constants;
use Madcoda\Youtube\Http\CurlHttpClient;
use Madcoda\Youtube\Http\HttpClientInterface;

/**
 * Youtube Data API (mainly apis for retrieving data)
 * @version 0.1
 */
class Youtube
{
    /*
     * some constants for convenience
     */

    //order in search api
    const ORDER_DATE = Constants::ORDER_DATE;
    const ORDER_RATING = Constants::ORDER_RATING;
    const ORDER_RELEVANCE = Constants::ORDER_RELEVANCE;
    const ORDER_TITLE = Constants::ORDER_TITLE;
    const ORDER_VIDEOCOUNT = Constants::ORDER_VIDEOCOUNT;
    const ORDER_VIEWCOUNT = Constants::ORDER_VIEWCOUNT;

    //eventType
    const EVENT_TYPE_LIVE = Constants::EVENT_TYPE_LIVE;
    const EVENT_TYPE_COMPLETED = Constants::EVENT_TYPE_COMPLETED;
    const EVENT_TYPE_UPCOMING = Constants::EVENT_TYPE_UPCOMING;

    //type in search api
    const SEARCH_TYPE_CHANNEL = Constants::SEARCH_TYPE_CHANNEL;
    const SEARCH_TYPE_PLAYLIST = Constants::SEARCH_TYPE_PLAYLIST;
    const SEARCH_TYPE_VIDEO = Constants::SEARCH_TYPE_VIDEO;


    /**
     * The API Key
     * @var string
     */
    protected $youtube_key;


    /**
     * @var string
     */
    protected $referer;

    /**
     * @var string
     */
    protected $sslPath = null;


    /**
     * Transport used for every request. Created on first use if not injected.
     *
     * @var \Madcoda\Youtube\Http\HttpClientInterface
     */
    protected $httpClient = null;


    /**
     * @var array
     */
    public $APIs = array(
        'videos.list' => 'https://www.googleapis.com/youtube/v3/videos',
        'search.list' => 'https://www.googleapis.com/youtube/v3/search',
        'channels.list' => 'https://www.googleapis.com/youtube/v3/channels',
        'playlists.list' => 'https://www.googleapis.com/youtube/v3/playlists',
        'playlistItems.list' => 'https://www.googleapis.com/youtube/v3/playlistItems',
        'activities' => 'https://www.googleapis.com/youtube/v3/activities',
    );


    /**
     * @var array
     */
    public $page_info = array();


    /**
     * Constructor
     * $youtube = new Youtube(array('key' => 'KEY HERE'))
     *
     * @param array $params
     * @throws \Exception
     */
    public function __construct($params = array(), $sslPath = null)
    {
        if (!is_array($params)) {
            throw new \InvalidArgumentException('The configuration options must be an array.');
        }

        if (!array_key_exists('key', $params) || empty($params['key'])) {
            throw new \InvalidArgumentException('Google API key is required, please visit http://code.google.com/apis/console');
        }
        $this->setApiKey($params['key']);

        if (array_key_exists('referer', $params)) {
            $this->setReferer($params['referer']);
        }

        if (array_key_exists('apis', $params)) {
            $this->setAPIs($params['apis']);
        }

        if ($sslPath !== null) {
            if (!file_exists($sslPath)) {
                throw new \InvalidArgumentException(
                    'The supplied SSL certificate path does not exist: ' . $sslPath
                );
            }
            $this->sslPath = $sslPath;
        }
    }


    /**
     * Update the API key, useful if you want to switch
     * multiple keys to avoid quota problem
     * @param $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->youtube_key = $apiKey;
    }

    /**
     * Override the API urls, so you can set them from a config
     * @param array $APIs
     */
    public function setAPIs(array $APIs)
    {
        $this->APIs = $APIs;
    }


    public function setReferer($referer)
    {
        $this->referer = $referer;

        // Keep an already-created default transport in step with the change.
        if ($this->httpClient instanceof CurlHttpClient) {
            $this->httpClient->setReferer($referer);
        }
    }

    /**
     * Supply the transport used for every request.
     *
     * Injecting a client lets the library be driven without a network
     * connection, which is how the offline test suite runs.
     *
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * The transport in use, creating the ext-curl default on first call.
     *
     * @return HttpClientInterface
     */
    public function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = new CurlHttpClient($this->referer, $this->sslPath);
        }

        return $this->httpClient;
    }

    /**
     * @param $vId
     * @return \StdClass|false the video resource, or false if not found
     * @throws \Exception
     */
    public function getVideoInfo($vId)
    {
        $API_URL = $this->getApi('videos.list');
        $params = array(
            'id' => $vId,
            'part' => 'id, snippet, contentDetails, player, statistics, status'
        );

        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }


    /**
     * @param $vIds
     * @return array|false     list of video resources, or false if none
     * @throws \Exception
     */
    public function getVideosInfo($vIds)
    {
        $ids = is_array($vIds) ? implode(',', $vIds) : $vIds;
        $API_URL = $this->getApi('videos.list');
        $params = array(
            'id' => $ids,
            'part' => 'id, snippet, contentDetails, player, statistics, status'
        );

        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeList($apiData);
    }


    /**
     * Simple search interface, this search all stuffs
     * and order by relevance
     *
     * @param $q
     * @param int $maxResults
     * @return array|false
     */
    public function search($q, $maxResults = 10)
    {
        $params = array(
            'q' => $q,
            'part' => 'id, snippet',
            'maxResults' => $maxResults
        );
        return $this->searchAdvanced($params);
    }


    /**
     * Search only videos
     *
     * @param  string $q Query
     * @param  integer $maxResults number of results to return
     * @param  string $order Order by
     * @return array|false     API results, or false if none
     */
    public function searchVideos($q, $maxResults = 10, $order = null)
    {
        $params = array(
            'q' => $q,
            'type' => 'video',
            'part' => 'id, snippet',
            'maxResults' => $maxResults
        );
        if (!empty($order)) {
            $params['order'] = $order;
        }

        return $this->searchAdvanced($params);
    }


    /**
     * Search only videos in the channel
     *
     * @param  string $q
     * @param  string $channelId
     * @param  integer $maxResults
     * @param  string $order
     * @return array|false
     */
    public function searchChannelVideos($q, $channelId, $maxResults = 10, $order = null)
    {
        $params = array(
            'q' => $q,
            'type' => 'video',
            'channelId' => $channelId,
            'part' => 'id, snippet',
            'maxResults' => $maxResults
        );
        if (!empty($order)) {
            $params['order'] = $order;
        }

        return $this->searchAdvanced($params);
    }


    public function searchChannelLiveStream($q, $channelId, $maxResults = 10, $order = null)
    {
        $params = array(
            'q' => $q,
            'type' => 'video',
            'eventType' => 'live',
            'channelId' => $channelId,
            'part' => 'id, snippet',
            'maxResults' => $maxResults
        );

        if (!empty($order)) {
            $params['order'] = $order;
        }

        return $this->searchAdvanced($params);
    }


    /**
     * Generic Search interface, use any parameters specified in
     * the API reference
     *
     * @param $params
     * @param $pageInfo
     * @return array|false     items, or array{results: array|false, info: array} when $pageInfo
     * @throws \Exception
     */
    public function searchAdvanced($params, $pageInfo = false)
    {
        $API_URL = $this->getApi('search.list');

        if (empty($params) || !isset($params['q'])) {
            throw new \InvalidArgumentException('at least the Search query must be supplied');
        }

        $apiData = $this->api_get($API_URL, $params);
        if ($pageInfo) {
            return array(
                'results' => $this->decodeList($apiData),
                'info'    => $this->page_info
            );
        } else {
            return $this->decodeList($apiData);
        }
    }


    /**
     * Generic Search Paginator, use any parameters specified in
     * the API reference and pass through nextPageToken as $token if set.
     *
     * @param $params
     * @param $token
     * @return array           array{results: array|false, info: array}
     */
    public function paginateResults($params, $token = null)
    {
        if (!is_null($token)) {
            $params['pageToken'] = $token;
        }
        return $this->searchAdvanced($params, true);
    }


    /**
     * @param $username
     * @return \StdClass|false the channel resource, or false if not found
     * @throws \Exception
     */
    public function getChannelByName($username, $optionalParams = false)
    {
        $API_URL = $this->getApi('channels.list');
        $params = array(
            'forUsername' => $username,
            'part' => 'id,snippet,contentDetails,statistics'
        );
        if (is_array($optionalParams) && $optionalParams) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }


    /**
     * @param $id
     * @return \StdClass|false the channel resource, or false if not found
     * @throws \Exception
     */
    public function getChannelById($id, $optionalParams = false)
    {
        $API_URL = $this->getApi('channels.list');
        $params = array(
            'id' => $id,
            'part' => 'id,snippet,contentDetails,statistics'
        );
        if (is_array($optionalParams) && $optionalParams) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }

    /**
     * @param array $ids
     * @return array|false     list of channel resources, or false if none
     * @throws \Exception
     */
    public function getChannelsById($ids = array(), $optionalParams = false)
    {
        $API_URL = $this->getApi('channels.list');
        $params = array(
            'id' => implode(',', $ids),
            'part' => 'id,snippet,contentDetails,statistics'
        );
        if (is_array($optionalParams) && $optionalParams) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeList($apiData);
    }

    /**
     * @param $channelId
     * @param array $optionalParams
     * @return array|false
     * @throws \Exception
     */
    public function getPlaylistsByChannelId($channelId, $optionalParams = array())
    {
        $params = array(
            'channelId' => $channelId,
            'part' => 'id, snippet, status'
        );
        if (is_array($optionalParams) && $optionalParams) {
            $params = array_merge($params, $optionalParams);
        }
        return $this->getPlaylistsByChannelIdAdvanced($params);
    }


    /**
     * Generic playlists.list interface, use any parameters specified in
     * the API reference. Pass a pageToken through $params and read the next
     * one back from the returned info to walk a channel with more playlists
     * than one page can hold.
     *
     * @param $params
     * @param bool|false $pageInfo
     * @return array|false     items, or array{results: array|false, info: array} when $pageInfo
     * @throws \Exception
     */
    public function getPlaylistsByChannelIdAdvanced($params, $pageInfo = false)
    {
        $API_URL = $this->getApi('playlists.list');

        if (empty($params) || !isset($params['channelId'])) {
            throw new \InvalidArgumentException('at least the channel id must be supplied');
        }

        $apiData = $this->api_get($API_URL, $params);
        if ($pageInfo) {
            return array(
                'results' => $this->decodeList($apiData),
                'info'    => $this->page_info
            );
        } else {
            return $this->decodeList($apiData);
        }
    }


    /**
     * @param $id
     * @return \StdClass|false the playlist resource, or false if not found
     * @throws \Exception
     */
    public function getPlaylistById($id)
    {
        $API_URL = $this->getApi('playlists.list');
        $params = array(
            'id' => $id,
            'part' => 'id, snippet, status'
        );
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }


    /**
     * @param $playlistId
     * @return array|false
     * @throws \Exception
     */
    public function getPlaylistItemsByPlaylistId($playlistId, $maxResults = 50)
    {
        $params = array(
            'playlistId' => $playlistId,
            'part' => 'id, snippet, contentDetails, status',
            'maxResults' => $maxResults
        );
        return $this->getPlaylistItemsByPlaylistIdAdvanced($params);
    }


    /**
     * @param $params
     * @param bool|false $pageInfo
     * @return array|false     items, or array{results: array|false, info: array} when $pageInfo
     * @throws \Exception
     */
    public function getPlaylistItemsByPlaylistIdAdvanced($params, $pageInfo = false)
    {
        $API_URL = $this->getApi('playlistItems.list');

        if (empty($params) || !isset($params['playlistId'])) {
            throw new \InvalidArgumentException('at least the playlist id must be supplied');
        }

        $apiData = $this->api_get($API_URL, $params);
        if ($pageInfo) {
            return array(
                'results' => $this->decodeList($apiData),
                'info'    => $this->page_info
            );
        } else {
            return $this->decodeList($apiData);
        }
    }


    /**
     * @param $channelId
     * @return array|false
     * @throws \Exception
     */
    public function getActivitiesByChannelId($channelId, $optionalParams = false)
    {
        if (empty($channelId)) {
            throw new \InvalidArgumentException('ChannelId must be supplied');
        }
        $API_URL = $this->getApi('activities');
        $params = array(
            'channelId' => $channelId,
            'part' => 'id, snippet, contentDetails'
        );
        if (is_array($optionalParams) && $optionalParams) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeList($apiData);
    }


    /**
     * Parse a youtube URL to get the youtube Vid.
     * Supports the full URL (www.youtube.com/watch?v=xxxx), the short URL
     * (youtu.be/xxxx), and the /embed/xxxx, /shorts/xxxx, /live/xxxx and
     * /v/xxxx path forms.
     *
     * @param  string $youtube_url
     * @throws \Exception
     * @return string Video Id
     */
    public static function parseVIdFromURL($youtube_url)
    {
        $videoId = null;
        // parse_url() only recognises a host when a scheme is present; without
        // one the whole string is treated as the path, so the fixed substr()
        // offsets below would slice the host instead of the id. Normalise first
        // so "youtu.be/xxxx" behaves like "https://youtu.be/xxxx".
        $url = $youtube_url;
        if (strpos($url, '//') === false) {
            $url = 'https://' . ltrim($url, '/');
        }

        // strpos() returns 0 when the URL *starts* with the needle, which is
        // falsy. Comparing against false is required, otherwise scheme-less
        // URLs such as "youtube.com/watch?v=..." are rejected as invalid.
        if (strpos($url, 'youtube.com') !== false) {
            // These forms carry the id in the path rather than in a ?v= param.
            // Match on the path so that "embed"/"shorts"/"live"/"v" appearing
            // anywhere else in the URL (a query value, or the id itself) is not
            // mistaken for a path of that kind. /live/ covers live streams and
            // the VODs they leave behind; /v/ is the legacy embed URL, still
            // common in old content.
            $path = static::_parse_url_path($url);
            foreach (array('/embed/', '/shorts/', '/live/', '/v/') as $prefix) {
                if (strpos($path, $prefix) === 0) {
                    // Only the first segment is the id; anything after it is a
                    // trailing slash or extra path we should not swallow.
                    $segments = explode('/', substr($path, strlen($prefix)));
                    $videoId = $segments[0];
                    break;
                }
            }

            // A ?v= param wins when present, but a query string on a path-style
            // URL (e.g. "/embed/xxxx?start=30") must not clobber the id found
            // above -- assigning null there made every such URL throw.
            $params = static::_parse_url_query($url);
            if (isset($params['v'])) {
                $videoId = $params['v'];
            }
        } elseif (strpos($url, 'youtu.be') !== false) {
            $path = static::_parse_url_path($url);
            // The whole path used to be taken as the id, so "youtu.be/xxxx/"
            // returned "xxxx/". Take the first segment only, exactly as the
            // path-prefix branch above does.
            $segments = explode('/', ltrim($path, '/'));
            $videoId = $segments[0];
        }

        if (empty($videoId)) {
            throw new \Exception('The supplied URL does not look like a Youtube URL');
        }

        return $videoId;
    }


    /**
     * Get the channel object by supplying the URL of the channel page
     *
     * @param  string $youtube_url
     * @throws \Exception
     * @return \StdClass|false Channel object, or false if not found
     */
    public function getChannelFromURL($youtube_url)
    {
        if (strpos($youtube_url, 'youtube.com') === false) {
            throw new \Exception('The supplied URL does not look like a Youtube URL');
        }

        $path = static::_parse_url_path($youtube_url);
        if (strpos($path, '/channel') === 0) {
            $segments = explode('/', $path);
            $channelId = $segments[count($segments) - 1];
            $channel = $this->getChannelById($channelId);
        } elseif (strpos($path, '/user') === 0) {
            $segments = explode('/', $path);
            $username = $segments[count($segments) - 1];
            $channel = $this->getChannelByName($username);
        } else {
            throw new \Exception('The supplied URL does not look like a Youtube Channel URL');
        }

        return $channel;
    }


    /*
     *  Internally used Methods, set visibility to public to enable more flexibility
     */

    /**
     * @param $name
     * @return mixed
     */
    public function getApi($name)
    {
        return $this->APIs[$name];
    }


    /**
     * Decode the response from youtube, extract the single resource object.
     * (Don't use this to decode the response containing list of objects)
     *
     * @param  string $apiData the api response from youtube
     * @throws \Exception
     * @return \StdClass|false a Youtube resource object, or false if absent
     */
    public function decodeSingle(&$apiData)
    {
        $resObj = json_decode($apiData);
        if (isset($resObj->error)) {
            $msg = "Error " . $resObj->error->code . " " . $resObj->error->message;
            if (isset($resObj->error->errors[0])) {
                $msg .= " : " . $resObj->error->errors[0]->reason;
            }
            throw new \Exception($msg, $resObj->error->code);
        } else {
            // A non-JSON body (an HTML error page, an empty response, a truncated
            // read) decodes to null. Passing that to property_exists() is a fatal
            // TypeError on PHP 8, where PHP 7 only raised a warning and carried on.
            if (!is_object($resObj) || !property_exists($resObj, 'items')) {
                return false;
            }
            $itemsArray = $resObj->items;
            if (!is_array($itemsArray) || count($itemsArray) == 0) {
                return false;
            } else {
                return $itemsArray[0];
            }
        }
    }


    /**
     * Decode the response from youtube, extract the list of resource objects
     *
     * @param  string $apiData response string from youtube
     * @throws \Exception
     * @return array|false      Array of StdClass objects, or false if empty
     */
    public function decodeList(&$apiData)
    {
        $resObj = json_decode($apiData);
        if (isset($resObj->error)) {
            $msg = "Error " . $resObj->error->code . " " . $resObj->error->message;
            if (isset($resObj->error->errors[0])) {
                $msg .= " : " . $resObj->error->errors[0]->reason;
            }
            throw new \Exception($msg, $resObj->error->code);
        } else {
            // Reset page_info on every call so a failed decode cannot leave the
            // caller reading stale pagination data from an earlier request.
            $this->page_info = array(
                'resultsPerPage' => isset($resObj->pageInfo->resultsPerPage) ? $resObj->pageInfo->resultsPerPage : null,
                'totalResults'   => isset($resObj->pageInfo->totalResults) ? $resObj->pageInfo->totalResults : null,
                'kind'           => isset($resObj->kind) ? $resObj->kind : null,
                'etag'           => isset($resObj->etag) ? $resObj->etag : null,
                'prevPageToken'     => null,
                'nextPageToken'     => null
            );
            if (isset($resObj->prevPageToken)) {
                $this->page_info['prevPageToken'] = $resObj->prevPageToken;
            }
            if (isset($resObj->nextPageToken)) {
                $this->page_info['nextPageToken'] = $resObj->nextPageToken;
            }

            if (!is_object($resObj) || !isset($resObj->items)) {
                return false;
            }
            $itemsArray = $resObj->items;
            if (!is_array($itemsArray) || count($itemsArray) == 0) {
                return false;
            } else {
                return $itemsArray;
            }
        }
    }


    /**
     * Using CURL to issue a GET request
     *
     * @param $url
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function api_get($url, $params)
    {
        //set the youtube key
        $params['key'] = $this->youtube_key;

        return $this->getHttpClient()->get($url, $params);
    }


    /**
     * Parse the input url string and return just the path part
     *
     * @param  string $url the URL
     * @return string      the path string
     */
    public static function _parse_url_path($url)
    {
        $path = parse_url($url, PHP_URL_PATH);

        // parse_url() returns null for a URL with no path at all. Returning a
        // string keeps substr() off the PHP 8.1+ "null to non-nullable" path.
        return $path === null || $path === false ? '' : $path;
    }


    /**
     * Parse the input url string and return an array of query params
     *
     * @param  string $url the URL
     * @return array      array of query params
     */
    public static function _parse_url_query($url)
    {
        $queryString = parse_url($url, PHP_URL_QUERY);

        $params = array();

        // parse_url() returns null when the URL carries no query string, and
        // passing null to parse_str() is deprecated on PHP 8.1+. This fires for
        // every URL without a query, including all /embed/ URLs.
        if ($queryString === null || $queryString === false) {
            $queryString = '';
        }

        parse_str($queryString, $params);

        if (count($params) === 0) {
            return $params;
        }

        return array_filter($params);
    }
}
