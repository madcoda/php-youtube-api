<?php

namespace Madcoda\Youtube\Tests\Unit;

use Madcoda\Youtube\Http\CurlHttpClient;
use Madcoda\Youtube\Tests\TestCase;

class CurlHttpClientTest extends TestCase
{
    /**
     * Exposed through a subclass rather than reflection: setAccessible() is a
     * no-op since PHP 8.1 and deprecated outright in 8.5, but is still required
     * on PHP 7.x, so reflection cannot be used cleanly across the whole range.
     *
     * @param  string $url
     * @param  array  $params
     * @return string
     */
    protected function buildUrl($url, array $params)
    {
        $client = new ExposedCurlHttpClient();

        return $client->publicBuildUrl($url, $params);
    }

    public function testAppendsAQueryStringToAPlainEndpoint()
    {
        $this->assertSame(
            'https://example.test/v3/videos?id=abc',
            $this->buildUrl('https://example.test/v3/videos', array('id' => 'abc'))
        );
    }

    /**
     * The old inline version appended nothing at all when the endpoint already
     * carried a query string, running the existing query into the new one:
     * ".../videos?part=id" + "key=K" produced ".../videos?part=idkey=K".
     */
    public function testJoinsWithAmpersandWhenTheEndpointAlreadyHasAQuery()
    {
        $this->assertSame(
            'https://example.test/v3/videos?part=id&key=K',
            $this->buildUrl('https://example.test/v3/videos?part=id', array('key' => 'K'))
        );
    }

    public function testDoesNotDoubleUpASeparator()
    {
        $this->assertSame(
            'https://example.test/v3/videos?key=K',
            $this->buildUrl('https://example.test/v3/videos?', array('key' => 'K'))
        );

        $this->assertSame(
            'https://example.test/v3/videos?a=1&key=K',
            $this->buildUrl('https://example.test/v3/videos?a=1&', array('key' => 'K'))
        );
    }

    public function testLeavesTheUrlAloneWhenThereAreNoParams()
    {
        $this->assertSame(
            'https://example.test/v3/videos',
            $this->buildUrl('https://example.test/v3/videos', array())
        );
    }

    public function testEncodesParameterValues()
    {
        $this->assertSame(
            'https://example.test/s?q=hello+world%26more',
            $this->buildUrl('https://example.test/s', array('q' => 'hello world&more'))
        );
    }

    /**
     * @group live
     */
    public function testATransportFailureBecomesAnException()
    {
        $client = new CurlHttpClient();

        $this->expectException('Exception');
        $client->get('http://this-host-does-not-exist.invalid/', array());
    }
}
