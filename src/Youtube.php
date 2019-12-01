<?php

namespace Madcoda\Youtube;

use Madcoda\Youtube\Constants;

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
     * @var array urls to be used
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
     * @var YoutubeQuota
     */
    public $quotaObj;


    /**
     * Constructor
     * $youtube = new Youtube(array('key' => 'KEY HERE'))
     *
     * @param array $params
     * @param string $sslPath
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
            $this->sslPath = $sslPath;
        }
    }

    /**
     * if quotaObj is set, we will be able to count quota used on each query
     */
    public function injectQuotaCalculator(YoutubeQuotas $quotaObj)
    {
        $this->quotaObj = $quotaObj;
    }

    /**
     * Update the API key.
     * Useful if you want to switch multiple keys to avoid quota problem.
     * 
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->youtube_key = $apiKey;
    }

    /**
     * Override the API urls, so you can set them from a config.
     * 
     * @param array $APIs
     */
    public function setAPIs($APIs)
    {
        $this->APIs = $APIs;
    }


    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    /**
     * getting video details.
     * 
     * @param string $vId
     * @return \StdClass
     * @throws \Exception
     */
    public function getVideoInfo($vId, $optionalParams = array())
    {
        $API_URL = $this->getApi($endPoint = 'videos.list');
        $params = array(
            'id' => $vId,
            'part' => 'id, snippet, contentDetails, player, statistics, status'
        );
        if (count($optionalParams)) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
            if (isset($this->quotaObj)) {
                $this->quotaObj->addQuery($endPoint, $params['part']);
            }
        return $this->decodeSingle($apiData);
    }


    /**
     * Getting videos detail.
     * 
     * @param array|string $vIds
     * @return \StdClass
     * @throws \Exception
     */
    public function getVideosInfo($vIds)
    {
        $ids = is_array($vIds) ? implode(',', $vIds) : $vIds;
        $API_URL = $this->getApi($endPoint = 'videos.list');
        $params = array(
            'id' => $ids,
            'part' => 'id, snippet, contentDetails, player, statistics, status'
        );

        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
        return $this->decodeList($apiData);
    }


    /**
     * Simple search interface, this search all stuffs and order by relevance.
     *
     * @param string $q
     * @param int $maxResults
     * @return array
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
     * Search only videos.
     *
     * @param  string $q Query
     * @param  integer $maxResults number of results to return
     * @param  string $order Order by
     * @return \StdClass  API results
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
     * Search only videos in the channel.
     *
     * @param  string $q
     * @param  string $channelId
     * @param  integer $maxResults
     * @param  string $order
     * @return object
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

    /**
     * Search only livestreams videos in the channel.
     *
     * @param  string $q
     * @param  string $channelId
     * @param  integer $maxResults
     * @param  string $order
     * @return object
     */
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
     * Generic Search interface, use any parameters specified in the API reference.
     *
     * @param array $params
     * @param boolean $pageInfo
     * @return array
     * @throws \Exception
     */
    public function searchAdvanced($params, $pageInfo = false)
    {
        $API_URL = $this->getApi($endPoint = 'search.list');

        if (empty($params) || !isset($params['q'])) {
            throw new \InvalidArgumentException('at least the Search query must be supplied');
        }

        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
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
     * Generic Search Paginator.
     * Use any parameters specified in the API reference and pass through nextPageToken as $token if set.
     *
     * @param array $params
     * @param string $token
     * @return array
     */
    public function paginateResults($params, $token = null)
    {
        if (!is_null($token)) {
            $params['pageToken'] = $token;
        }
        return $this->searchAdvanced($params, true);
    }


    /**
     * Getting channel infos by username.
     * 
     * @param string $username
     * @return \StdClass
     * @throws \Exception
     */
    public function getChannelByName($username, $optionalParams = array())
    {
        $API_URL = $this->getApi($endPoint = 'channels.list');
        $params = array(
            'forUsername' => $username,
            'part' => 'id,snippet,contentDetails,statistics,invideoPromotion'
        );
        if (count($optionalParams)) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
        return $this->decodeSingle($apiData);
    }


    /**
     * Getting channel infos by channelId.
     * 
     * @param string $id
     * @param array $optionalParams
     * @return \StdClass
     * @throws \Exception
     */
    public function getChannelById($id, $optionalParams = array())
    {
        $API_URL = $this->getApi($endPoint = 'channels.list');
        $params = array(
            'id' => $id,
            'part' => 'id,snippet,contentDetails,statistics,invideoPromotion'
        );
        if (count($optionalParams)) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
        return $this->decodeSingle($apiData);
    }

    /**
     * Getting channels infos by channelId.
     * 
     * @param array $ids
     * @param array $optionalParams
     * @return \StdClass
     * @throws \Exception
     */
    public function getChannelsById($ids = array(), $optionalParams = array())
    {
        $API_URL = $this->getApi($endPoint = 'channels.list');
        $params = array(
            'id' => implode(',', $ids),
            'part' => 'id,snippet,contentDetails,statistics,invideoPromotion'
        );
        if (count($optionalParams)) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
        return $this->decodeList($apiData);
    }

    /**
     * Getting playlists for specified channelId.
     * 
     * @param $channelId
     * @param array $optionalParams
     * @return array
     * @throws \Exception
     */
    public function getPlaylistsByChannelId($channelId, $optionalParams = array())
    {
        $API_URL = $this->getApi($endPoint = 'playlists.list');
        $params = array(
            'channelId' => $channelId,
            'part' => 'id, snippet, status'
        );
        if (count($optionalParams)) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
        return $this->decodeList($apiData);
    }


    /**
     * Getting playlists details for specified playlistId.
     * 
     * @param string $id
     * @return \StdClass
     * @throws \Exception
     */
    public function getPlaylistById($playlistId)
    {
        $API_URL = $this->getApi($endPoint = 'playlists.list');
        $params = array(
            'id' => $playlistId,
            'part' => 'id, snippet, status'
        );
        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
        return $this->decodeSingle($apiData);
    }


    /**
     * Getting videos that are in specified playlists.
     * 
     * @param string $playlistId
     * @return array
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
     * Getting videos that are in specified playlists and pageInfo.
     * PageInfo is required if you need to parse more than $maxresults into one playlist.
     * 
     * @param $params
     * @param bool|false $pageInfo
     * @return array
     * @throws \Exception
     */
    public function getPlaylistItemsByPlaylistIdAdvanced($params, $pageInfo = false)
    {
        $API_URL = $this->getApi($endPoint = 'playlistItems.list');

        if (empty($params) || !isset($params['playlistId'])) {
            throw new \InvalidArgumentException('at least the playlist id must be supplied');
        }

        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
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
     * Getting activities on channel.
     * 
     * @param string $channelId
     * @param array $optionalParams
     * @return array
     * @throws \Exception
     */
    public function getActivitiesByChannelId($channelId, $optionalParams = array())
    {
        if (empty($channelId)) {
            throw new \InvalidArgumentException('ChannelId must be supplied');
        }
        $API_URL = $this->getApi($endPoint = 'activities');
        $params = array(
            'channelId' => $channelId,
            'part' => 'id, snippet, contentDetails'
        );
        if (count($optionalParams)) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->api_get($API_URL, $params);
        if (isset($this->quotaObj)) {
            $this->quotaObj->addQuery($endPoint, $params['part']);
        }
        return $this->decodeList($apiData);
    }


    /**
     * Parse a youtube URL to get the youtube Vid.
     * Support both full URL (www.youtube.com) and short URL (youtu.be)
     *
     * @param string $youtube_url
     * @throws \Exception
     * @return string Video Id
     */
    public static function parseVIdFromURL($youtube_url)
    {
        $videoId = null;
        if (strpos($youtube_url, 'youtube.com')) {
            if (strpos($youtube_url, 'embed')) {
                $path = static::_parse_url_path($youtube_url);
                $videoId = substr($path, 7);
            }
            if ($params = static::_parse_url_query($youtube_url)) {
                $videoId = isset($params['v']) ? $params['v'] : null;
            }
        } elseif (strpos($youtube_url, 'youtu.be')) {
            $path = static::_parse_url_path($youtube_url);
            $videoId = substr($path, 1);
        }

        if (empty($videoId)) {
            throw new \InvalidArgumentException('The supplied URL does not look like a Youtube URL');
        }

        return $videoId;
    }


    /**
     * Get the channel object by supplying the URL of the channel page
     *
     * @param  string $youtube_url
     * @throws \Exception
     * @return object Channel object
     */
    public function getChannelFromURL($youtube_url)
    {
        if (strpos($youtube_url, 'youtube.com') === false) {
            throw new \InvalidArgumentException('The supplied URL does not look like a Youtube URL');
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
            throw new \InvalidArgumentException('The supplied URL does not look like a Youtube Channel URL');
        }

        return $channel;
    }


    /*
     *  Internally used Methods, set visibility to public to enable more flexibility
     */

    /**
     * @param string $name
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
     * @param string $apiData the api response from youtube
     * @throws \Exception
     * @return \StdClass  an Youtube resource object
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
     * @return array Array of StdClass objects
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
            $this->page_info = array(
                'resultsPerPage' => $resObj->pageInfo->resultsPerPage,
                'totalResults'   => $resObj->pageInfo->totalResults,
                'kind'           => $resObj->kind,
                'etag'           => $resObj->etag,
                'prevPageToken'     => null,
                'nextPageToken'     => null
            );
            if (isset($resObj->prevPageToken)) {
                $this->page_info['prevPageToken'] = $resObj->prevPageToken;
            }
            if (isset($resObj->nextPageToken)) {
                $this->page_info['nextPageToken'] = $resObj->nextPageToken;
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

        //boilerplates for CURL
        $tuCurl = curl_init();
        if ($this->sslPath !== null) {
            curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($tuCurl, CURLOPT_CAINFO, __DIR__ . '/cert/cacert.pem');
            curl_setopt($tuCurl, CURLOPT_CAPATH, __DIR__ . '/cert/cacert.pem');
        }
        curl_setopt($tuCurl, CURLOPT_URL, $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($params));
        if ($this->referer !== null) {
            curl_setopt($tuCurl, CURLOPT_REFERER, $this->referer);
        }
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        $tuData = curl_exec($tuCurl);
        if (curl_errno($tuCurl)) {
            throw new \InvalidArgumentException('Curl Error : ' . curl_error($tuCurl), curl_errno($tuCurl));
        }
        return $tuData;
    }


    /**
     * Parse the input url string and return just the path part
     *
     * @param  string $url the URL
     * @return string      the path string
     */
    public static function _parse_url_path($url)
    {
        return parse_url($url, PHP_URL_PATH);
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

        parse_str($queryString, $params);

        if (count($params) === 0) {
            return $params;
        }

        return array_filter($params);
    }
}
