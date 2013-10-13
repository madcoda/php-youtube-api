<?php

namespace Madcoda\Tests;

require_once('./vendor/autoload.php');

use Madcoda\Youtube;

/**
 * Class YoutubeTest
 *
 * @category Youtube
 * @package  Youtube
 * @author   Jason Leung <jason@madcoda.com>
 */
class YoutubeTest extends \PHPUnit_Framework_TestCase
{

	var $youtube;

	public function __construct()
	{
	}

	public function setUp()
    {
    	$TEST_API_KEY = 'AIzaSyDDefsgXEZu57wYgABF7xEURClu4UAzyB8';
        $this->youtube = new Youtube(array('key' => $TEST_API_KEY));
    }

    public function tearDown()
    {
        
    }


    /**
     * @expectedException Exception
     */
    public function testConstructorFail(){
    	$this->youtube = new Youtube(array());
    }

    /**
     * @expectedException Exception
     */
    public function testConstructorFail2(){
    	$this->youtube = new Youtube('FAKE API KEY');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage	Error 400 Bad Request : keyInvalid
     */
    public function testInvalidApiKey(){
    	$this->youtube = new Youtube(array('key'=> 'nonsense'));
    	$vID = 'rie-hPVJ7Sw';
    	$this->youtube->getVideoInfo($vID);
    }

    public function testGetVideoInfo(){
    	$vID = 'rie-hPVJ7Sw';
    	$response = $this->youtube->getVideoInfo($vID);
    	//print_r($response);
    	$this->assertEquals($vID, $response->id);
    	$this->assertNotNull('response');
    	$this->assertEquals('youtube#video', $response->kind);
    	//add all these assertions here in case the api is changed,
    	//we can detect it instantly
    	$this->assertObjectHasAttribute('statistics', $response);
    	$this->assertObjectHasAttribute('status', $response);
    	$this->assertObjectHasAttribute('snippet', $response);
    	$this->assertObjectHasAttribute('contentDetails', $response);
    }

    public function testSearch(){
    	$response = $this->youtube->search('Android');
    	//print_r($response);
    }

    public function testGetChannelByName(){
    	$response = $this->youtube->getChannelByName('Google');
    	//print_r($response);

    	$this->assertEquals('youtube#channel', $response->kind);
    	//This is not a safe Assertion because the name can change, but include it anyway
    	$this->assertEquals('Google', $response->snippet->title);
    	//add all these assertions here in case the api is changed,
    	//we can detect it instantly
    	$this->assertObjectHasAttribute('snippet', $response);
    	$this->assertObjectHasAttribute('contentDetails', $response);
    	$this->assertObjectHasAttribute('statistics', $response);
    	$this->assertObjectHasAttribute('topicDetails', $response);
    }

    public function testGetChannelById(){
    	$channelId = 'UCk1SpWNzOs4MYmr0uICEntg';
    	$response = $this->youtube->getChannelById($channelId);
    	//print_r($response);

    	$this->assertEquals('youtube#channel', $response->kind);
    	$this->assertEquals($channelId, $response->id);
    	$this->assertObjectHasAttribute('snippet', $response);
    	$this->assertObjectHasAttribute('contentDetails', $response);
    	$this->assertObjectHasAttribute('statistics', $response);
    	$this->assertObjectHasAttribute('topicDetails', $response);
    }

    public function testGetPlaylistsByChannelId(){
    	$GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
    	$response = $this->youtube->getPlaylistsByChannelId($GOOGLE_CHANNELID);

    	$this->assertTrue(count($response) > 0);
    	$this->assertEquals('youtube#playlist', $response[0]->kind);
    	$this->assertEquals('Google', $response[0]->snippet->channelTitle);

    	//print_r($response);
    }

    public function testGetPlaylistById(){

    	//get one of the playlist
    	$GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
    	$response = $this->youtube->getPlaylistsByChannelId($GOOGLE_CHANNELID);
    	$playlist = $response[0];

    	$response = $this->youtube->getPlaylistById($playlist->id);
    	$this->assertEquals('youtube#playlist', $response->kind);
    	
    }

    public function testParseVIdFromURLFull(){
    	$vId = $this->youtube->parseVIdFromURL('http://www.youtube.com/watch?v=1FJHYqE0RDg');
    	$this->assertEquals('1FJHYqE0RDg', $vId);
    }

    public function testParseVIdFromURLShort(){
    	$vId = $this->youtube->parseVIdFromURL('http://youtu.be/1FJHYqE0RDg');
    	$this->assertEquals('1FJHYqE0RDg', $vId);	
    }


}