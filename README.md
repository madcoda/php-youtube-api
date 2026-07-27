php-youtube-api
===============

[![Tests](https://github.com/madcoda/php-youtube-api/actions/workflows/tests.yml/badge.svg)](https://github.com/madcoda/php-youtube-api/actions/workflows/tests.yml)
[![Static analysis](https://github.com/madcoda/php-youtube-api/actions/workflows/static.yml/badge.svg)](https://github.com/madcoda/php-youtube-api/actions/workflows/static.yml)
[![Latest Stable Version](https://poser.pugx.org/madcoda/php-youtube-api/v/stable)](https://packagist.org/packages/madcoda/php-youtube-api)
[![Total Downloads](https://poser.pugx.org/madcoda/php-youtube-api/downloads)](https://packagist.org/packages/madcoda/php-youtube-api)
[![License](https://poser.pugx.org/madcoda/php-youtube-api/license)](https://packagist.org/packages/madcoda/php-youtube-api)

A basic PHP wrapper for the Youtube Data API v3 (non-OAuth). Designed to let devs easily
fetch public data (video, channel and playlist info) from Youtube. No third-party runtime
dependencies.

The reason for returning the decoded JSON response directly is that you only need to read
the Google API doc to use this library, instead of learning another set of APIs on top of
it. Keep it simple.

Some parameters are missing from this library simply because they were not needed at the
time. If you want a particular feature, please
[file an issue](https://github.com/madcoda/php-youtube-api/issues).

OAuth endpoints are out of scope: anything requiring an "authorized request" is not
supported.

## Requirements

* PHP 7.0 or newer, including PHP 8.5
* the `curl` extension
* the `json` extension

Every release is tested on PHP 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5.

| PHP       | Status                                                                        |
|-----------|-------------------------------------------------------------------------------|
| 7.0 – 7.4 | Supported. All are past end of life; upgrading is strongly recommended.        |
| 8.0 – 8.1 | Supported. Both are past end of life.                                          |
| 8.2 – 8.5 | Supported and actively maintained.                                             |

The 1.x line keeps the PHP 7.0 floor so existing installs are not broken. It will be
raised in 2.0.

## Install

```sh
composer require madcoda/php-youtube-api
```

## Getting started

Please read the wiki on how to use this library with
[PHP with composer](https://github.com/madcoda/php-youtube-api/wiki/started-with-php-composer),
[Laravel 4](https://github.com/madcoda/php-youtube-api/wiki/started-with-laravel-4) and
[Laravel 5](https://github.com/madcoda/php-youtube-api/wiki/started-with-laravel-5).

For the functions implemented in this library, see the
[API reference](https://github.com/madcoda/php-youtube-api/wiki/api-reference).

### Plain PHP

```php
require 'vendor/autoload.php';

$youtube = new Madcoda\Youtube\Youtube(array('key' => 'YOUR API KEY'));
$video = $youtube->getVideoInfo('rie-hPVJ7Sw');
```

### Laravel

The package is auto-discovered on Laravel 5.5 and newer, so there is nothing to register.
Publish the config and set your key:

```sh
php artisan vendor:publish --provider="Madcoda\Youtube\YoutubeServiceProviderLaravel5"
```

```php
$video = Youtube::getVideoInfo(request('vid', 'dQw4w9WgXcQ'));
```

On Laravel 5.0 to 5.4, register the provider and alias manually in `config/app.php`:

```php
'providers' => [
    Madcoda\Youtube\YoutubeServiceProviderLaravel5::class,
],
'aliases' => [
    'Youtube' => Madcoda\Youtube\Facades\Youtube::class,
],
```

> The Laravel 4 provider is deprecated and will be removed in 2.0. Laravel 4 has been end
> of life since 2015, and the provider relies on `package()` and `bindShared()`, both
> removed in Laravel 5.

## Format of returned data

The returned JSON is decoded into PHP objects, not arrays. See the
["Reference" section](https://developers.google.com/youtube/v3/docs/) of the official API
doc for the shape of each resource.

Methods that fetch a single resource return `false` when nothing matches; methods that
fetch a list return `false` when the list is empty. API errors are thrown as `\Exception`
with the Google error code as the exception code.

## Using your own HTTP client

Requests go through a small transport interface, so you can route them through your own
HTTP stack, add retries or logging, or return canned responses in your own tests.

```php
use Madcoda\Youtube\Http\HttpClientInterface;

class MyClient implements HttpClientInterface
{
    public function get($url, array $params)
    {
        // return the raw, undecoded response body
    }
}

$youtube = new Madcoda\Youtube\Youtube(array('key' => 'YOUR API KEY'));
$youtube->setHttpClient(new MyClient());
```

Without one, the library uses `Madcoda\Youtube\Http\CurlHttpClient`, which behaves exactly
as before.

## Development

The test suite runs offline against recorded fixtures, so it needs no API key:

```sh
composer install
vendor/bin/phpunit
```

A second suite exercises the real API to catch changes in Google's response shapes. It is
excluded by default and needs a key:

```sh
YOUTUBE_API_KEY=your-key vendor/bin/phpunit --group live
```

No `composer.lock` is committed, which is deliberate: each supported PHP version has to be
free to resolve the PHPUnit major that supports it, from 5.7 on PHP 7.0 up to 11.5 on
PHP 8.5.

## Youtube Data API v3

- [YouTube Data API v3 doc](https://developers.google.com/youtube/v3/)
- [Obtain an API key from the Google API Console](https://console.cloud.google.com/apis/credentials)

## Contact

For bugs, complaints or suggestions, please
[file an issue](https://github.com/madcoda/php-youtube-api/issues) or email
jason@madcoda.com.

## License

`madcoda/php-youtube-api` is licensed under the [MIT License](http://opensource.org/licenses/MIT).
