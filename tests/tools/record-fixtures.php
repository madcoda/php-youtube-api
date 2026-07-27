<?php

/**
 * Re-record tests/fixtures/*.json from live API responses.
 *
 *     YOUTUBE_API_KEY=... php tests/tools/record-fixtures.php
 *
 * The offline suite asserts against these files, so they are only as truthful
 * as the last time this was run. FixtureDriftTest (in the live group) compares
 * them against live responses and fails when they have drifted, which is the
 * signal to run this again.
 *
 * Costs roughly 310 quota units, almost all of it the three search calls at
 * 100 units each. Every other endpoint used here costs 1.
 *
 * Implemented on top of HttpClientInterface: the recorder is just a decorator
 * that keeps the raw body on its way past.
 */

require __DIR__ . '/../../src/Constants.php';
require __DIR__ . '/../../src/Http/HttpClientInterface.php';
require __DIR__ . '/../../src/Http/CurlHttpClient.php';
require __DIR__ . '/../../src/Youtube.php';
require __DIR__ . '/../Subjects.php';

use Madcoda\Youtube\Http\CurlHttpClient;
use Madcoda\Youtube\Http\HttpClientInterface;
use Madcoda\Youtube\Tests\Subjects;
use Madcoda\Youtube\Youtube;

class RecordingHttpClient implements HttpClientInterface
{
    /** @var string */
    public $lastBody;

    /** @var CurlHttpClient */
    private $inner;

    public function __construct()
    {
        $this->inner = new CurlHttpClient();
    }

    public function get($url, array $params)
    {
        $this->lastBody = $this->inner->get($url, $params);

        return $this->lastBody;
    }
}

$key = getenv('YOUTUBE_API_KEY');
if (empty($key)) {
    fwrite(STDERR, "YOUTUBE_API_KEY is not set.\n");
    exit(1);
}

$dir = __DIR__ . '/../fixtures';
$recorder = new RecordingHttpClient();
$youtube = new Youtube(array('key' => $key));
$youtube->setHttpClient($recorder);

$written = 0;
$failed = 0;

/**
 * Run a call and save whatever body came back over the wire.
 */
function record($name, callable $call)
{
    global $recorder, $dir, $written, $failed;

    try {
        $call();
    } catch (\Exception $e) {
        // An error body is still a valid thing to record; only a transport
        // failure means there is nothing to write.
        if (empty($recorder->lastBody)) {
            printf("  FAIL %-22s %s\n", $name, $e->getMessage());
            $failed++;

            return;
        }
    }

    $decoded = json_decode($recorder->lastBody);
    if ($decoded === null) {
        printf("  FAIL %-22s response was not JSON\n", $name);
        $failed++;

        return;
    }

    // search.list echoes back the region the request appeared to come from,
    // which would commit whoever ran this recorder's approximate location into
    // a public repository. Nothing asserts on it, and the drift test compares
    // field names rather than values, so pinning it loses nothing.
    if (isset($decoded->regionCode)) {
        $decoded->regionCode = 'US';
    }

    // Pretty-printed so that a re-record produces a readable diff. This is the
    // same JSON, just reformatted; the library only ever json_decode()s it.
    $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($dir . '/' . $name . '.json', $json . "\n");

    $count = isset($decoded->items) ? count($decoded->items) : 0;
    printf("  ok   %-22s %6d bytes, %d item(s)\n", $name, strlen($json), $count);
    $written++;
}

echo "Recording fixtures into tests/fixtures\n\n";

record('videos.single', function () use ($youtube) {
    $youtube->getVideoInfo(Subjects::VIDEO);
});

record('videos.multiple', function () use ($youtube) {
    $youtube->getVideosInfo(array(Subjects::VIDEO, Subjects::VIDEO_SECOND));
});

record('videos.empty', function () use ($youtube) {
    $youtube->getVideoInfo(Subjects::VIDEO_UNAVAILABLE);
});

record('channels.single', function () use ($youtube) {
    $youtube->getChannelById(Subjects::CHANNEL);
});

record('channels.multiple', function () use ($youtube) {
    $youtube->getChannelsById(array(Subjects::CHANNEL, Subjects::CHANNEL_SECOND));
});

// Page one, keeping the token so page two can be recorded with a prevPageToken.
$playlistsNextPageToken = null;
record('playlists', function () use ($youtube, &$playlistsNextPageToken) {
    $youtube->getPlaylistsByChannelId(Subjects::CHANNEL_SECOND, array('maxResults' => 3));
    $playlistsNextPageToken = $youtube->page_info['nextPageToken'];
});

record('playlists.page2', function () use ($youtube, &$playlistsNextPageToken) {
    $youtube->getPlaylistsByChannelIdAdvanced(array(
        'channelId'  => Subjects::CHANNEL_SECOND,
        'part'       => 'id, snippet, status',
        'maxResults' => 3,
        'pageToken'  => $playlistsNextPageToken,
    ));
});

record('playlistItems', function () use ($youtube) {
    $youtube->getPlaylistItemsByPlaylistId(Subjects::PLAYLIST, 3);
});

record('activities', function () use ($youtube) {
    $youtube->getActivitiesByChannelId(Subjects::CHANNEL_SECOND, array('maxResults' => 3));
});

// Page one, keeping the token so page two can be recorded with a prevPageToken.
$nextPageToken = null;
record('search', function () use ($youtube, &$nextPageToken) {
    $youtube->searchAdvanced(array(
        'q'          => Subjects::SEARCH_QUERY,
        'type'       => 'video',
        'channelId'  => Subjects::CHANNEL_SECOND,
        'part'       => 'id, snippet',
        'maxResults' => 3,
    ));
    $nextPageToken = $youtube->page_info['nextPageToken'];
});

record('search.page2', function () use ($youtube, &$nextPageToken) {
    $youtube->searchAdvanced(array(
        'q'          => Subjects::SEARCH_QUERY,
        'type'       => 'video',
        'channelId'  => Subjects::CHANNEL_SECOND,
        'part'       => 'id, snippet',
        'maxResults' => 3,
        'pageToken'  => $nextPageToken,
    ));
});

record('search.empty', function () use ($youtube) {
    $youtube->searchAdvanced(array(
        'q'          => Subjects::SEARCH_QUERY_NO_RESULTS,
        'type'       => 'video',
        'part'       => 'id, snippet',
        'maxResults' => 3,
    ));
});

// A genuine 400 from Google, rather than a hand-written approximation.
record('error.badkey', function () use ($recorder) {
    $bad = new Youtube(array('key' => 'nonsense-key-for-fixture-recording'));
    $bad->setHttpClient($recorder);
    $bad->getVideoInfo(Subjects::VIDEO);
});

echo "\n";
printf("%d written, %d failed\n", $written, $failed);
echo "\nNote: error.quota.json is not recorded here. Exhausting the daily quota\n";
echo "on purpose is not a reasonable thing for this script to do, so that one\n";
echo "fixture stays hand-written from Google's documented error shape.\n";

exit($failed > 0 ? 1 : 0);
