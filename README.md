php-youtube-api
===============

A basic PHP wrapper for the Youtube Data API v3 ( Non-OAuth ). Designed to let devs easily 
fetch public data (Video, Channel, Playlists info) from Youtube. No 3rd party dependancy. (except PHPUnit)
The reason of returning the decoded JSON response directly is that you only need to read the Google API doc 
to use this library, instead of learning my set of API again (Keep it simple).

Well...actually some parameters are missing in this library, because I don't need them at this point, if you desire a particular feature please file an issue here :)

Currently will not consider adding OAuth endpoints. (those required "authorized request")


## Install
Installation is easy with composer just run.

```sh
composer require madcoda/php-youtube-api
```

## Usage (Plain PHP project)

```php
require 'vendor/autoload.php';

use Madcoda\Youtube;

$youtube = new Youtube(array('key' => '/* Your API key here */'));

// Return a std PHP object 
$video = $youtube->getVideoInfo('rie-hPVJ7Sw');

// Search playlists, channels and videos, Return an array of PHP objects
$results = $youtube->search('Android');

// Search only Videos, Return an array of PHP objects
$videoList = $youtube->searchVideos('Android');

// Search only Videos in a given channel, Return an array of PHP objects
$videoList = $youtube->searchChannelVideos('keyword', 'UCk1SpWNzOs4MYmr0uICEntg', 100);

$results = $youtube->searchAdvanced(array( /* params */ ));

// Return a std PHP object
$channel = $youtube->getChannelByName('xdadevelopers');

// Return a std PHP object
$channel = $youtube->getChannelById('UCk1SpWNzOs4MYmr0uICEntg');

// Return a std PHP object
$playlist = $youtube->getPlaylistById('PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs');

// Return an array of PHP objects
$playlists = $youtube->getPlaylistsByChannelId('UCk1SpWNzOs4MYmr0uICEntg');

// Return an array of PHP objects
$playlistItems = $youtube->getPlaylistItemsByPlaylistId('PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs');

// Return an array of PHP objects
$activities = $youtube->getActivitiesByChannelId('UCk1SpWNzOs4MYmr0uICEntg');

// Parse Youtube URL into videoId
$videoId = $youtube->parseVIdFromURL('https://www.youtube.com/watch?v=moSFlvxnbgk');
// result: moSFlvxnbgk
```

## Basic Search Pagination
```php
use Madcoda\Youtube;

$youtube = new Youtube(array('key' => '/* Your API key here */'));

// Set Default Parameters
$params = array(
    'q'             => 'Android',
    'type'          => 'video',
    'part'          => 'id, snippet',
    'maxResults'    => 50
);

// Make Intial Call. With second argument to reveal page info such as page tokens.
$search = $youtube->searchAdvanced($params, true);

// check if we have a pageToken
if (isset($search['info']['nextPageToken'])) {
    $params['pageToken'] = $search['info']['nextPageToken'];
}

// Make Another Call and Repeat
$search = $youtube->searchAdvanced($params, true);          

// add results key with info parameter set
print_r($search['results']); 

/* Alternative approach with new built in paginateResults function */
 
// Same Params as before
$params = array(
    'q'             => 'Android',
    'type'          => 'video',
    'part'          => 'id, snippet',
    'maxResults'    => 50
);

// an array to store page tokens so we can go back and forth
$pageTokens   = array();

// make inital search
$search       = $youtube->paginateResults($params, null);

// store token
$pageTokens[] = $search['info']['nextPageToken'];

// go to next page in result
$search       = $youtube->paginateResults($params, $pageTokens[0]);

// store token
$pageTokens[] = $search['info']['nextPageToken'];

// go to next page in result
$search       = $youtube->paginateResults($params, $pageTokens[1]);

// store token
$pageTokens[] = $search['info']['nextPageToken'];

// go back a page
$search       = $youtube->paginateResults($params, $pageTokens[0]);

// add results key with info parameter set
print_r($search['results']);

```

The pagination above is quite basic. Depending on what you are trying to achieve; you may want to create a recurssive function that traverses the results.

## Usage (Laravel Project)
Add the dependency in the composer.json, then run 

```bash
$ composer update
```

Since the Laravel framework also configured to autoload composer dependencies (in bootstrap/autoload.php),
You don't need to add any require or include statements, just use the class

app/controllers/YoutubeController.php

```php
class YoutubeController extends BaseController {

    public function index()
    {
        $youtube = new Madcoda\Youtube(array('key' => '/* Your API key here */'));
	    print_r($youtube->getVideoInfo(Input::get('vid')));
    }

}
```

If you want to use this class as "Youtube", you can add an aliases, edit the app/config/app.php,
Insert the following line:

```php
'aliases' => array(
     //...
    'Youtube' => 'Madcoda\Youtube',
),
```

## Run Unit Test
If you have PHPUnit installed in your environment, just run

```bash
$ phpunit
```

If you don't have PHPUnit installed, you can run this

```bash
$ composer update
$ ./vendor/bin/phpunit
```

## Format of returned data
The returnd json is decoded as PHP objects (not Array).
Please read the ["Reference" section](https://developers.google.com/youtube/v3/docs/) of the Official API doc.


## Youtube Data API v3
- [Youtube Data API v3 Doc](https://developers.google.com/youtube/v3/)
- [Obtain API key from Google API Console](http://code.google.com/apis/console)

## Contact
For bugs, complain and suggestions please [file an Issue here](https://github.com/madcoda/php-youtube-api/issues) 
or send email to jason@madcoda.com :)
