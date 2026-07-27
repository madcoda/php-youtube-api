<?php

namespace Madcoda\Youtube\Tests\Integration;

use Madcoda\Youtube\Http\CurlHttpClient;
use Madcoda\Youtube\Http\HttpClientInterface;
use Madcoda\Youtube\Tests\Subjects;
use Madcoda\Youtube\Tests\TestCase;
use Madcoda\Youtube\Youtube;

/**
 * Guards the offline suite against its own fixtures going stale.
 *
 * The unit tests are only as truthful as tests/fixtures, and a recorded
 * response is a snapshot of an API that Google changes without notice. Nothing
 * would otherwise tell us that the offline suite had drifted from reality; it
 * would keep passing against a shape the API no longer returns.
 *
 * This fetches each fixture's live counterpart and compares the *structure*,
 * not the values. Values change constantly -- view counts, etags, whichever
 * video NASA uploaded this morning -- and asserting on them would be noise.
 *
 * The direction that matters is fixture-to-live: a key present in the fixture
 * but absent from the live response means the API dropped a field the offline
 * tests may be relying on. The reverse, a new field appearing live, is only
 * worth knowing about, so it is reported rather than failed.
 *
 * Regenerate with: YOUTUBE_API_KEY=... php tests/tools/record-fixtures.php
 *
 * @group live
 */
class FixtureDriftTest extends TestCase
{
    /**
     * How deep to compare. Two levels covers the response envelope and each
     * item's top-level sections (snippet, contentDetails, statistics, status),
     * which is where a breaking change would show up. Going deeper starts
     * catching legitimately optional fields -- a maxres thumbnail, a channel's
     * defaultLanguage -- and turns into a flaky test rather than a useful one.
     */
    const DEPTH = 2;

    /**
     * @var Youtube
     */
    protected $youtube;

    protected function set_up()
    {
        $key = getenv('YOUTUBE_API_KEY');

        if (empty($key)) {
            $this->markTestSkipped('Set YOUTUBE_API_KEY to run the live API tests.');
        }

        $this->youtube = new Youtube(array('key' => $key));
    }

    /**
     * Collect dotted key paths, collapsing every array index to "*" so that a
     * three-item response compares equal to a two-item one.
     *
     * @param  mixed  $value
     * @param  string $prefix
     * @param  int    $depth
     * @return array
     */
    private function keyPaths($value, $prefix = '', $depth = 0)
    {
        $paths = array();

        if ($depth > self::DEPTH) {
            return $paths;
        }

        if (is_object($value)) {
            foreach (get_object_vars($value) as $key => $child) {
                $path = $prefix === '' ? $key : $prefix . '.' . $key;
                $paths[] = $path;
                $paths = array_merge($paths, $this->keyPaths($child, $path, $depth + 1));
            }
        } elseif (is_array($value)) {
            // Union across every element, not just the first. The API does not
            // promise an item order, and optional fields differ between items:
            // Youtube's own channel carries snippet.country while NASA's does
            // not. Comparing position 0 to position 0 therefore reports fields
            // as removed purely because the two responses happened to be
            // ordered differently.
            foreach ($value as $child) {
                $paths = array_merge($paths, $this->keyPaths($child, $prefix . '.*', $depth));
            }
        }

        return $paths;
    }

    /**
     * Capture the raw body of whatever call the callable makes.
     *
     * @param  callable $call
     * @return string
     */
    private function capture(callable $call)
    {
        $recorder = new DriftRecordingClient();
        $this->youtube->setHttpClient($recorder);

        try {
            $call($this->youtube);
        } catch (\Exception $e) {
            if ($recorder->lastBody === null) {
                throw $e;
            }
        }

        return $recorder->lastBody;
    }

    /**
     * @return array
     */
    public static function fixtureProvider()
    {
        return array(
            'videos.single' => array('videos.single', function (Youtube $y) {
                $y->getVideoInfo(Subjects::VIDEO);
            }),
            'videos.multiple' => array('videos.multiple', function (Youtube $y) {
                $y->getVideosInfo(array(Subjects::VIDEO, Subjects::VIDEO_SECOND));
            }),
            'channels.single' => array('channels.single', function (Youtube $y) {
                $y->getChannelById(Subjects::CHANNEL);
            }),
            'channels.multiple' => array('channels.multiple', function (Youtube $y) {
                $y->getChannelsById(array(Subjects::CHANNEL, Subjects::CHANNEL_SECOND));
            }),
            'playlists' => array('playlists', function (Youtube $y) {
                $y->getPlaylistsByChannelId(Subjects::CHANNEL_SECOND, array('maxResults' => 3));
            }),
            'playlistItems' => array('playlistItems', function (Youtube $y) {
                $y->getPlaylistItemsByPlaylistId(Subjects::PLAYLIST, 3);
            }),
            'activities' => array('activities', function (Youtube $y) {
                $y->getActivitiesByChannelId(Subjects::CHANNEL_SECOND, array('maxResults' => 3));
            }),
            'search' => array('search', function (Youtube $y) {
                $y->searchAdvanced(array(
                    'q'          => Subjects::SEARCH_QUERY,
                    'type'       => 'video',
                    'channelId'  => Subjects::CHANNEL_SECOND,
                    'part'       => 'id, snippet',
                    'maxResults' => 3,
                ));
            }),
            'error.badkey' => array('error.badkey', function (Youtube $y) {
                $y->setApiKey('nonsense-key-for-fixture-recording');
                $y->getVideoInfo(Subjects::VIDEO);
            }),
        );
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testFixtureStillMatchesTheLiveResponse($name, $call)
    {
        $live = json_decode($this->capture($call));
        $this->assertNotNull($live, 'Live response for ' . $name . ' was not JSON.');

        $recorded = $this->fixtureData($name);

        $livePaths = array_unique($this->keyPaths($live));
        $fixturePaths = array_unique($this->keyPaths($recorded));

        $gone = array_values(array_diff($fixturePaths, $livePaths));
        $added = array_values(array_diff($livePaths, $fixturePaths));

        if ($added) {
            // Not a failure: new fields are additive and break nothing. Worth
            // surfacing so a re-record can pick them up.
            fwrite(STDERR, sprintf(
                "\n  note: %s.json has no record of new live field(s): %s\n",
                $name,
                implode(', ', $added)
            ));
        }

        $this->assertSame(array(), $gone, sprintf(
            'tests/fixtures/%s.json contains field(s) the API no longer returns: %s. '
            . 'Re-record with: php tests/tools/record-fixtures.php',
            $name,
            implode(', ', $gone)
        ));
    }
}

/**
 * Keeps the raw body on its way past, so the structure can be compared before
 * the library decodes it.
 */
class DriftRecordingClient implements HttpClientInterface
{
    /** @var string */
    public $lastBody = null;

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
