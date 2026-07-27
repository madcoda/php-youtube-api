# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-07-27

URL parsing that keeps up with how Youtube links are actually shared today, and a
way to page through a channel's playlists. Additive only, so this is a drop-in
upgrade from 1.3.0, with one narrow behaviour change noted under Changed.

### Added

- `parseVIdFromURL()` now understands Shorts URLs (`youtube.com/shorts/xxxx`).
  Reported in [#79](https://github.com/madcoda/php-youtube-api/pull/79) by
  [@malbero](https://github.com/malbero).
- `parseVIdFromURL()` now understands two further path forms:
  `youtube.com/live/xxxx`, which is what live streams and the VODs they leave
  behind are shared as, and the legacy embed URL `youtube.com/v/xxxx`, which is
  still present in a lot of older content. Both accept a query string and a
  trailing slash, and both work with or without `www` and with or without a
  scheme, exactly as `/embed/` and `/shorts/` already did. Requested in
  [#85](https://github.com/madcoda/php-youtube-api/issues/85).
- `getPlaylistsByChannelIdAdvanced($params, $pageInfo = false)`, which takes raw
  `playlists.list` parameters and can return the pagination envelope alongside
  the results. `getPlaylistsByChannelId()` returned one page and gave the caller
  no way to read `nextPageToken`, so a channel with more playlists than a single
  page holds was silently truncated; passing a `pageToken` through `$params` and
  reading the next one back from `info` now walks the whole channel.
  `getPlaylistsByChannelId()` is unchanged for existing callers and is now a thin
  wrapper over the new method, the same way `getPlaylistItemsByPlaylistId()`
  wraps its own `...Advanced()` counterpart. Originally proposed in
  [#71](https://github.com/madcoda/php-youtube-api/pull/71) by
  [@tyteck](https://github.com/tyteck).

### Changed

- `getPlaylistsByChannelId()` now rejects a `null` channel id before making a
  request, throwing `InvalidArgumentException` the way the other `...Advanced()`
  methods already do for their required ids. Previously the `null` was passed
  through as an empty `channelId` parameter and the request was sent anyway, so
  the caller paid a quota unit to have the API reject it. Any other value,
  including the empty string, reaches the API exactly as before. Callers that
  catch `\Exception` are unaffected, since `InvalidArgumentException` extends it.

### Fixed

- `parseVIdFromURL()` discarded the video id whenever a path-style URL carried a
  query string, because the `?v=` branch assigned `null` unconditionally when no
  `v` parameter was present. `youtube.com/embed/xxxx?start=30` and
  `youtube.com/shorts/xxxx?feature=share` both threw
  "The supplied URL does not look like a Youtube URL". A `?v=` parameter still
  takes precedence when one is actually present. Also from
  [#79](https://github.com/madcoda/php-youtube-api/pull/79).
- `/embed/` was matched anywhere in the URL rather than at the start of the path,
  so the id was parsed from the wrong offset for URLs that merely contained the
  word. Both `/embed/` and `/shorts/` are now matched as path prefixes, and only
  the first path segment is taken as the id, so a trailing slash is no longer
  included in the returned id.
- A short URL written with a trailing slash, `youtu.be/xxxx/`, returned the id
  with the slash still attached (`xxxx/`), which then produced 404s from the API
  for anyone passing the result straight through. The `youtu.be` branch took the
  whole path rather than its first segment; it now follows the same rule as the
  path-prefix forms. Reported alongside
  [#85](https://github.com/madcoda/php-youtube-api/issues/85).

## [1.3.0] - 2026-07-27

Support for current PHP, with the PHP 7.0 floor kept intact. There are no public
API removals, so this is a drop-in upgrade from 1.2.x.

### Added

- Support for PHP 8.0 through 8.5. The library is now tested on all of
  7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5.
- `Madcoda\Youtube\Http\HttpClientInterface` and `CurlHttpClient`, so the
  transport can be replaced. `Youtube::setHttpClient()` and
  `Youtube::getHttpClient()` expose it. This is what lets the test suite run
  without a network connection or an API key.
- Laravel package auto-discovery. The provider and the `Youtube` alias are
  registered automatically on Laravel 5.5 and newer.
- GitHub Actions CI across the full supported matrix, plus PHPStan (level 5)
  and PSR-12 checks.
- `tests/tools/record-fixtures.php`, which re-records every fixture from live
  responses in one command, and a fixture-drift test that fails when a fixture
  records a field the API has stopped returning. Together these stop the
  offline suite from silently asserting against a shape Youtube no longer
  produces.
- `ext-json` is now declared. The library has always used `json_decode()`.

### Fixed

- **Fatal on PHP 8**: `decodeSingle()` passed the decoded body straight to
  `property_exists()`. A response that is not JSON (an HTML error page, an empty
  body, a truncated read) decodes to `null`, which is a `TypeError` on PHP 8.
  PHP 7 only raised a warning and returned `false`. Any caller on PHP 8 hit a
  hard crash whenever the API returned a non-JSON response.
- **Fatal on PHP 8**: passing a truthy non-array `$optionalParams` to
  `getChannelByName()`, `getChannelById()`, `getChannelsById()`,
  `getPlaylistsByChannelId()` or `getActivitiesByChannelId()` reached
  `array_merge()` and raised a `TypeError`. Such values are now ignored.
- `decodeList()` read `pageInfo`, `kind`, `etag` and `items` without checking
  them, producing five warnings per failed decode on PHP 8.
- `page_info` is now reset on every decode. A failed decode previously left the
  caller reading pagination data from the previous, successful request.
- `parse_str()` was given `parse_url()`'s `null` when a URL had no query string,
  deprecated on PHP 8.1+. This fired for every `/embed/` URL.
- `parseVIdFromURL()` used `strpos()` as a boolean, so a URL *starting* with
  `youtube.com` or `youtu.be` produced offset `0` and was rejected as invalid.
  Scheme-less URLs such as `youtu.be/xxxx` and `youtube.com/watch?v=xxxx` are
  now parsed correctly.
- The `$sslPath` constructor argument was stored but never used as a path:
  `CURLOPT_CAINFO` and `CURLOPT_CAPATH` were both hardcoded to the CA bundle
  vendored in `src/cert`, silently replacing the system trust store with a
  2013-vintage snapshot, and `CURLOPT_CAPATH` was given a file where it expects
  a directory. The supplied path is now honoured, and a path that does not exist
  raises `InvalidArgumentException` at construction instead of quietly using the
  wrong certificates.
- An endpoint that already carried a query string had new parameters appended
  with no separator, so `.../videos?part=id` plus `key=K` became
  `.../videos?part=idkey=K`. Reachable by anyone overriding `$APIs` or the
  `apis` config key.
- 18 `@return` docblocks were wrong. `getVideosInfo()`, `searchVideos()`,
  `searchChannelVideos()` and `getChannelsById()` were all documented as
  returning `\StdClass` when they return arrays, and neither `decodeSingle()`
  nor `decodeList()` documented that they can return `false`.
- The Laravel 5 provider declared `protected $defer = true`, which Laravel has
  ignored since 5.8 replaced it with the `DeferrableProvider` interface.
- The Laravel 5 provider now merges its shipped config defaults. Previously, if
  the config had not been published, the binding failed with "the configuration
  options must be an array" rather than reporting the missing API key.
- The Laravel 5 provider guards its `config_path()` call, which does not exist
  on Lumen and made `boot()` fatal there.

### Changed

- The test suite no longer requires an API key or network access. It runs
  against recorded fixtures captured from real API responses. The tests that
  talk to the real API are kept in a separate `live` group, excluded by
  default, and run on a schedule in CI.
- Test subjects were replaced with durable ones and centralised in
  `tests/Subjects.php`. The previous suite used whatever was to hand in 2013:
  one of its videos has since been deleted outright, and two others turned out
  to live on unofficial re-upload channels rather than the rights holders'.
  Playlist tests now use a channel's uploads playlist, which cannot be deleted
  while the channel exists, instead of a curated playlist that can.
- Code style moved from PSR-2 to PSR-12, enforced by PHP-CS-Fixer instead of
  StyleCI. Class constants keep no visibility modifier, because `public const`
  is PHP 7.1+ syntax and would not parse on the 7.0 floor.
- `composer.lock` is no longer committed. A library should not ship one, and it
  pinned the dev toolchain to PHPUnit 5.7 for everyone.

### Removed

- `src/compat.php`. It claimed to alias the pre-PSR-4 `Madcoda\Youtube` class,
  but was actually named `Madcoda\compat` and aliased nothing. It has been dead
  since the PSR-4 move in 2016 and was loaded on every autoload dump.
- `.travis.yml`. travis-ci.org shut down in 2021.

### Deprecated

- `YoutubeServiceProviderLaravel4`. Laravel 4 has been end of life since 2015,
  and the provider calls `package()` and `bindShared()`, both removed in
  Laravel 5, so it cannot work on any supported framework version. It will be
  removed in 2.0.
- `src/cert/cacert.pem`. This CA bundle is no longer referenced by the library
  and its contents date from 2013. It will be removed in 2.0; rely on the system
  trust store, or pass an explicit path to the constructor.

## [1.2.6] and earlier

See the [releases page](https://github.com/madcoda/php-youtube-api/releases).
