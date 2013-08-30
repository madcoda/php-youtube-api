<?php

namespace Madcoda;


/**
 * Youtube Data API (mainly apis for retrieving data)
 * @version 0.1
 */
class Youtube {


    private $youtube_key = ''; //pass in by constructor


    var $APIs = array(
        'videos.list' => 'https://www.googleapis.com/youtube/v3/videos',
        'search.list' => 'https://www.googleapis.com/youtube/v3/search',
        'channels.list' => 'https://www.googleapis.com/youtube/v3/channels',
        'playlists.list' => 'https://www.googleapis.com/youtube/v3/playlists',
    );


    /**
     * Constructor
     * $youtube = new Youtube(array('key' => 'KEY HERE'))
     * @param array $params 
     */
    public function __construct($params){
        $this->youtube_key = $params['key'];
    }


	public function getVideoInfo($vId){
        $API_URL = $this->getApi('videos.list');
        $params = array(
                'id' => $vId,
                'key' => $this->youtube_key,
                'part' => 'id, snippet, contentDetails, player, statistics, status'
            );
        
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }


    public function search($q){
        $API_URL = $this->getApi('search.list');
        $params = array(
            'q' => $q,
            'part' => 'id, snippet',
            'maxResults' => 10
        );

        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeList($apiData);
    }


    public function getChannelByName($username){
        $API_URL = $this->getApi('channels.list');
        $params = array(
            'forUsername' => $username,
            'part' => 'id,snippet,contentDetails, statistics,topicDetails,invideoPromotion'
        );
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }


    public function getChannelById($id){
        $API_URL = $this->getApi('channels.list');
        $params = array(
            'id' => $id,
            'part' => 'id,snippet,contentDetails, statistics,topicDetails,invideoPromotion'
        );
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }



    public function getPlaylistsByChannelId($channelId){
        $API_URL = $this->getApi('playlists.list');
        $params = array(
            'channelId' => $channelId,
            'part' => 'id, snippet, status'
        );
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeList($apiData);
    }


    public function getPlaylistById($id){
        $API_URL = $this->getApi('playlists.list');
        $params = array(
            'id' => $id,
            'part' => 'id, snippet, status'
        );
        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeSingle($apiData);
    }




    /*
     *  Private Methods 
     */
    

    private function getApi($name){
        return $this->APIs[$name];
    }

    
    private function decodeSingle(&$apiData){
        $resObj = json_decode($apiData);
        $itemsArray = $resObj->items;
        
        if(!is_array($itemsArray) || count($itemsArray) == 0){
            return FALSE;
        }else{
            return $itemsArray[0];
        }
    }


    private function decodeList(&$apiData){
        $resObj = json_decode($apiData);
        $itemsArray = $resObj->items;
        
        if(!is_array($itemsArray) || count($itemsArray) == 0){
            return FALSE;
        }else{
            return $itemsArray;
        }
    }


    private function api_get($url, $params){
        //set the youtube key
        $params['key'] = $this->youtube_key;

        //boilerplates for CURL
        $tuCurl = curl_init();  
        curl_setopt($tuCurl, CURLOPT_URL, $url.(strpos($url, '?') === FALSE ? '?' : '').http_build_query($params));
        if(strpos($url, 'https') === FALSE){
            curl_setopt($tuCurl, CURLOPT_PORT , 80);
        }else{
            curl_setopt($tuCurl, CURLOPT_PORT , 443);
        }
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        $tuData = curl_exec($tuCurl);
        if(!curl_errno($tuCurl)){ 
          //$info = curl_getinfo($tuCurl); 
          //echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url']; 
        } else { 
          //echo 'Curl error: ' . curl_error($tuCurl); 
        }
        return $tuData;
    }

}