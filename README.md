php-youtube-api
===============
[![Build Status](https://secure.travis-ci.org/madcoda/php-youtube-api.png)](https://travis-ci.org/madcoda/php-youtube-api)

A basic PHP wrapper for the Youtube Data API v3 ( Non-OAuth ). Designed to let devs easily 
fetch public data (Video, Channel, Playlists info) from Youtube. No 3rd party dependancy. (except PHPUnit)
The reason of returning the decoded JSON response directly is that you only need to read the Google API doc 
to use this library, instead of learning my set of API again (Keep it simple).

Well...actually some parameters are missing in this library, because I don't need them at this point, if you desire a particular feature please file an issue here :)

Currently will not consider adding OAuth endpoints. (those required "authorized request")


## Install
Edit your `composer.json` and add this to the `require` section
```
"madcoda/php-youtube-api": "1.*"
```

```sh
composer update
```

## Getting started
Please read the wiki on how to use this library with [PHP with composer](https://github.com/madcoda/php-youtube-api/wiki/started-with-php-composer), [Laravel 4](https://github.com/madcoda/php-youtube-api/wiki/started-with-laravel-4) and [Laravel 5](https://github.com/madcoda/php-youtube-api/wiki/started-with-laravel-5).

For the functions implemented in this library, please visis [API Reference](https://github.com/madcoda/php-youtube-api/wiki/api-reference)


## Format of returned data
The returnd json is decoded as PHP objects (not Array).
Please read the ["Reference" section](https://developers.google.com/youtube/v3/docs/) of the Official API doc.


## Youtube Data API v3
- [Youtube Data API v3 Doc](https://developers.google.com/youtube/v3/)
- [Obtain API key from Google API Console](http://code.google.com/apis/console)

## Contact
For bugs, complain and suggestions please [file an Issue here](https://github.com/madcoda/php-youtube-api/issues) 
or send email to jason@madcoda.com :)
