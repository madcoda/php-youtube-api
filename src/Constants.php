<?php

namespace Madcoda\Youtube;

/**
 * Laravel users should use this class to access these constants
 */
class Constants
{

    //order in search api
    const ORDER_DATE = 'date';
    const ORDER_RATING = 'rating';
    const ORDER_RELEVANCE = 'relevance';
    const ORDER_TITLE = 'title';
    const ORDER_VIDEOCOUNT = 'videoCount';
    const ORDER_VIEWCOUNT = 'viewCount';

    //eventType
    const EVENT_TYPE_LIVE = 'live';
    const EVENT_TYPE_COMPLETED = 'completed';
    const EVENT_TYPE_UPCOMING = 'upcoming';

    //type in search api
    const SEARCH_TYPE_CHANNEL = 'channel';
    const SEARCH_TYPE_PLAYLIST = 'playlist';
    const SEARCH_TYPE_VIDEO = 'video';
}
