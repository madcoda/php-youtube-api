<?php

namespace Madcoda\Youtube\Tests\Unit;

use Madcoda\Youtube\Tests\TestCase;
use Madcoda\Youtube\Youtube;

class UrlParsingTest extends TestCase
{
    public static function validUrlProvider()
    {
        return array(
            'watch'                 => array('http://www.youtube.com/watch?v=1FJHYqE0RDg', '1FJHYqE0RDg'),
            'watch https'           => array('https://www.youtube.com/watch?v=1FJHYqE0RDg', '1FJHYqE0RDg'),
            'watch without www'     => array('https://youtube.com/watch?v=1FJHYqE0RDg', '1FJHYqE0RDg'),
            'watch extra params'    => array('https://www.youtube.com/watch?v=1FJHYqE0RDg&t=42s', '1FJHYqE0RDg'),
            'watch param not first' => array('https://www.youtube.com/watch?t=42s&v=1FJHYqE0RDg', '1FJHYqE0RDg'),
            'embed'                 => array('https://www.youtube.com/embed/_vA5PvDfNTs', '_vA5PvDfNTs'),
            'short'                 => array('http://youtu.be/1FJHYqE0RDg', '1FJHYqE0RDg'),
            'short https'           => array('https://youtu.be/1FJHYqE0RDg', '1FJHYqE0RDg'),

            // Regression: strpos() was used as a boolean, so a URL *starting*
            // with the needle gave offset 0 and was rejected as invalid.
            'scheme-less watch'     => array('youtube.com/watch?v=1FJHYqE0RDg', '1FJHYqE0RDg'),
            'scheme-less short'     => array('youtu.be/1FJHYqE0RDg', '1FJHYqE0RDg'),
            'scheme-less embed'     => array('youtube.com/embed/_vA5PvDfNTs', '_vA5PvDfNTs'),

            // Protocol-relative URLs, as produced by embed snippets.
            'protocol-relative'     => array('//www.youtube.com/watch?v=1FJHYqE0RDg', '1FJHYqE0RDg'),

            // Shorts, which put the id in the path like /embed/ does.
            'shorts'                => array('https://www.youtube.com/shorts/j6bTvzE0Xtk', 'j6bTvzE0Xtk'),
            'shorts without www'    => array('https://youtube.com/shorts/j6bTvzE0Xtk', 'j6bTvzE0Xtk'),
            'scheme-less shorts'    => array('youtube.com/shorts/j6bTvzE0Xtk', 'j6bTvzE0Xtk'),
            'shorts trailing slash' => array('https://www.youtube.com/shorts/j6bTvzE0Xtk/', 'j6bTvzE0Xtk'),

            // Regression: the query branch assigned null whenever a path-style
            // URL carried any query string, discarding the id just parsed out
            // of the path. Both of these threw before.
            'shorts with query'     => array('https://youtube.com/shorts/j6bTvzE0Xtk?feature=share', 'j6bTvzE0Xtk'),
            'embed with query'      => array('https://www.youtube.com/embed/_vA5PvDfNTs?start=30', '_vA5PvDfNTs'),
            'embed trailing slash'  => array('https://www.youtube.com/embed/_vA5PvDfNTs/', '_vA5PvDfNTs'),

            // "embed" and "shorts" only count as a path prefix, never as a
            // substring of the id or of a query value.
            'id containing embed'   => array('https://www.youtube.com/watch?v=embedFHYqE0R', 'embedFHYqE0R'),
            'shorts in query value' => array('https://www.youtube.com/watch?v=1FJHYqE0RDg&list=shorts', '1FJHYqE0RDg'),
        );
    }

    /**
     * @dataProvider validUrlProvider
     */
    public function testParsesVideoIdFromUrl($url, $expected)
    {
        $this->assertSame($expected, Youtube::parseVIdFromURL($url));
    }

    public static function malformedUrlProvider()
    {
        return array(
            'scheme only'    => array('https://'),
            'typo host'      => array('http://www.yuotube.com'),
            'other site'     => array('http://www.facebook.com'),
            'empty string'   => array(''),
            'no video id'    => array('https://www.youtube.com/watch'),
            'v param absent' => array('https://www.youtube.com/watch?t=42s'),
            'bare shorts'    => array('https://www.youtube.com/shorts/'),
            'bare embed'     => array('https://www.youtube.com/embed/'),
        );
    }

    /**
     * @dataProvider malformedUrlProvider
     */
    public function testRejectsNonYoutubeUrls($url)
    {
        $this->expectException('Exception');
        Youtube::parseVIdFromURL($url);
    }

    public function testParsesQueryStringIntoParams()
    {
        $params = Youtube::_parse_url_query('https://www.youtube.com/watch?v=abc&t=42');

        $this->assertSame('abc', $params['v']);
        $this->assertSame('42', $params['t']);
    }

    public function testReturnsEmptyArrayWhenThereIsNoQueryString()
    {
        // parse_url() yields null here; passing that to parse_str() is
        // deprecated on PHP 8.1+, so the null must be coerced first.
        $this->assertCount(0, Youtube::_parse_url_query('http://404.php.net'));
    }

    public function testPathIsAlwaysAStringEvenWhenAbsent()
    {
        $this->assertSame('', Youtube::_parse_url_path('https://youtu.be'));
    }
}
