<?php

namespace Madcoda\Youtube;

/**
 * Youtube Data API quota calculator.
 * Youtube api is working with quotas and it may be a good thing to know how much quota 
 * we are using on some request. Actually (nov 2019), any google dev account has 10k/day quotas.
 * One search query is 100 quotas for 50 results. Make 200 of them and your quota is over.
 * 
 * @version 1
 */
class YoutubeQuotas
{
    protected $quotaUsed = 0;
    protected $partParams = [];

    /**
     * classic constructor.
     * Do not mix create and new YoutubeQuota
     * 
     * @param string endpoint to be used
     * @param string partParams used to collect some specific data
     * @return YoutubeQuotas object
     */
    public function __construct(string $apiUsed = null, string $partParams = null)
    {
        if (isset($apiUsed) && !empty($apiUsed)) {
            $this->addQuery($apiUsed, $partParams);
        }
    }

    /**
     * classic constructor.
     * Do not mix create and new YoutubeQuota
     * 
     * @param string endpoint to be used
     * @param string partParams used to collect some specific data
     */
    public function addQuery(string $apiUsed, string $partParams = null)
    {
        if (isset($partParams) && !empty($partParams)) {
            $this->extractPartParams($partParams);
        }
        switch ($apiUsed) {
            case 'videos.list':
                $this->videosListQuotaCalculator();
                break;
            case 'search.list':
                $this->searchListQuotaCalculator();
                break;
            case 'channels.list':
                $this->channelsListQuotaCalculator();
                break;
            case 'playlists.list':
                $this->playlistsListQuotaCalculator();
                break;
            case 'playlistItems.list':
                $this->playlistsItemsListQuotaCalculator();
                break;
            case 'activities':
                /** @todo */
                break;
        }
    }

    /**
     * Static constructor
     * 
     * @param string endpoint to be used
     * @param string partParams used to collect some specific data
     * @return YoutubeQuotas object
     */
    public function create(string $apiUsed = null, string $partParams = null)
    {
        return new static($apiUsed, $partParams);
    }

    /**
     * channels.list calculator.
     * initialize one array of params and their cost.
     */
    public function channelsListQuotaCalculator()
    {
        $paramsAllowedAndQuotasUsed = [
            'id' => 0,
            'snippet' => 2,
            'brandingSettings' => 2,
            'contentDetails' => 2,
            'invideoPromotion' => 2,
            'statistics' => 2,
            'status' => 2,
            'topicDetails' => 2,
        ];
        /** base query is 1 point */
        $this->quotaUsed += 1;
        if ($this->partParams) {
            $this->quotaCalculator($paramsAllowedAndQuotasUsed);
        }
    }

    /**
     * videos.list calculator.
     * initialize one array of params and their cost.
     */
    public function videosListQuotaCalculator()
    {
        $paramsAllowedAndQuotasUsed = [
            'id' => 0,
            'contentDetails' => 2,
            'fileDetails' => 1,
            'liveStreamingDetails' => 2,
            'localizations' => 2,
            'player' => 0,
            'processingDetails' => 1,
            'recordingDetails' => 2,
            'snippet' => 2,
            'statistics' => 2,
            'status' => 2,
            'suggestions' => 1,
            'topicDetails' => 2,
        ];
        /** base query is 1 point */
        $this->quotaUsed += 1;
        if ($this->partParams) {
            $this->quotaCalculator($paramsAllowedAndQuotasUsed);
        }
    }

    /**
     * playlists.list calculator.
     * initialize one array of params and their cost.
     */
    public function playlistsListQuotaCalculator()
    {
        $paramsAllowedAndQuotasUsed = [
            'id' => 0,
            'player' => 0,
            'contentDetails' => 2,
            'localizations' => 2,
            'snippet' => 2,
            'status' => 2,
        ];
        /** base query is 1 point */
        $this->quotaUsed += 1;
        if ($this->partParams) {
            $this->quotaCalculator($paramsAllowedAndQuotasUsed);
        }
    }

    /**
     * playlistItems.list calculator.
     * initialize one array of params and their cost.
     */
    public function playlistsItemsListQuotaCalculator()
    {
        $paramsAllowedAndQuotasUsed = [
            'id' => 0,
            'contentDetails' => 2,
            'snippet' => 2,
            'status' => 2,
        ];
        /** base query is 1 point */
        $this->quotaUsed += 1;
        if ($this->partParams) {
            $this->quotaCalculator($paramsAllowedAndQuotasUsed);
        }
    }

    /**
     * search.list calculator.
     * initialize one array of params and their cost.
     */
    public function searchListQuotaCalculator()
    {
        /** base query for search is 100 points but there is no params */
        $this->quotaUsed += 100;
    }

    /**
     * main calculator.
     * according to an array of param and points will calculate the quota_used by the query(ies).
     * 
     * @param array params and the quota they consume
     */
    protected function quotaCalculator(array $allowedParamsAndQuotaUsed)
    {
        foreach ($this->partParams as $index => $part) {
            if (isset($allowedParamsAndQuotaUsed[$part])) {
                $this->quotaUsed += $allowedParamsAndQuotaUsed[$part];
            } else {
                // cleaning false entries in params
                unset($this->partParams[$index]);
            }
        }
    }

    /**
     * extract/cleanify the params to sent to yt api.
     * 
     * @param string list of params comma separated as in Youtube.php
     */
    protected function extractPartParams(string $partParams)
    {
        //removing extra whitespaces then explode it then remove duplicates
        $this->partParams = array_unique(explode(',', preg_replace('/\s+/', '', $partParams)));
    }

    /**
     * getter quotaUsed.
     * 
     * @return integer quota used on query(ies)
     */
    public function quotaUsed()
    {
        return $this->quotaUsed;
    }

    /**
     * getter params cleanified.
     * 
     * @return array list of params for last query
     */
    public function partParams()
    {
        return $this->partParams;
    }
}
