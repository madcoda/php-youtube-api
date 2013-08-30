php-youtube-api
===============

PHP wrapper for the Youtube Data API v3 ( Non-OAuth )

## Youtube Data API v3
- [Youtube Data API v3 Doc](https://developers.google.com/youtube/v3/)
- [Obtain API key from Google API Console](http://code.google.com/apis/console)


## Install
(Since this project has not been listed in Packagist, so specify a repository here)
Example composer.json

    {
	    "name": "MyCoolProject",
	    "require": {
	        "madcoda/php-youtube-api" : "dev-master"
	    },
	    "repositories": [
	        {
	            "type": "vcs",
	            "url": "https://github.com/madcoda/php-youtube-api.git"
	        }
	    ]
    }

Run the Install

    composer install

    //run "composer update" instead if you already has a composer.json before
    composer update


## Examples (Plain PHP project)

	require 'vendor/autoload.php';

    $youtube = new Madcoda\Youtube(array('key' => '/* Your API key here */'));

    // Return a std PHP object 
    $video = $youtube->getVideoInfo('rie-hPVJ7Sw');

    // Return an array of PHP objects
    $videoList = $youtube->search('Android');

    // Return a std PHP object
    $channel = $youtube->getChannelByName('xdadevelopers');

    // Return a std PHP object
    $channel = $youtube->getChannelById('UCk1SpWNzOs4MYmr0uICEntg');

    // Return a std PHP object
    $playlist = $youtube->getPlaylistById('PLgLZvFga2ml60JPoPDDKE7ZN7JPgXGmlu');

    // Return an array of PHP objects
    $playlists = $youtube->getPlaylistsByChannelId('UCk1SpWNzOs4MYmr0uICEntg');
