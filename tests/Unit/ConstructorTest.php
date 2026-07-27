<?php

namespace Madcoda\Youtube\Tests\Unit;

use Madcoda\Youtube\Http\CurlHttpClient;
use Madcoda\Youtube\Tests\Fake\ArrayHttpClient;
use Madcoda\Youtube\Tests\TestCase;
use Madcoda\Youtube\Youtube;

class ConstructorTest extends TestCase
{
    public function testRequiresAnArrayOfOptions()
    {
        $this->expectException('InvalidArgumentException');
        new Youtube('FAKE API KEY');
    }

    public function testRequiresAnApiKey()
    {
        $this->expectException('InvalidArgumentException');
        new Youtube(array());
    }

    public function testRejectsAnEmptyApiKey()
    {
        $this->expectException('InvalidArgumentException');
        new Youtube(array('key' => ''));
    }

    public function testAcceptsAKey()
    {
        $youtube = new Youtube(array('key' => 'TESTKEY'));

        $this->assertInstanceOf('Madcoda\Youtube\Youtube', $youtube);
    }

    public function testAcceptsAnApisOverride()
    {
        $youtube = new Youtube(array(
            'key'  => 'TESTKEY',
            'apis' => array('videos.list' => 'https://example.test/videos'),
        ));

        $this->assertSame('https://example.test/videos', $youtube->getApi('videos.list'));
    }

    public function testDefaultsToTheCurlTransport()
    {
        $youtube = new Youtube(array('key' => 'TESTKEY'));

        $this->assertInstanceOf('Madcoda\Youtube\Http\CurlHttpClient', $youtube->getHttpClient());
    }

    public function testAnInjectedTransportIsUsed()
    {
        $youtube = new Youtube(array('key' => 'TESTKEY'));
        $fake = new ArrayHttpClient('{}');
        $youtube->setHttpClient($fake);

        $this->assertSame($fake, $youtube->getHttpClient());
    }

    /**
     * The path was previously stored but never used: CAINFO and CAPATH were
     * both hardcoded to the CA bundle vendored in src/cert. A path that does
     * not exist now fails at construction rather than silently falling back to
     * the wrong certificates.
     */
    public function testRejectsAnSslPathThatDoesNotExist()
    {
        $this->expectException('InvalidArgumentException');
        new Youtube(array('key' => 'TESTKEY'), '/no/such/ca/bundle');
    }

    public function testAcceptsAnSslPathThatExists()
    {
        $youtube = new Youtube(array('key' => 'TESTKEY'), __DIR__);

        $this->assertInstanceOf('Madcoda\Youtube\Youtube', $youtube);
    }

    public function testRefererIsForwardedToTheDefaultTransport()
    {
        $youtube = new Youtube(array('key' => 'TESTKEY', 'referer' => 'https://example.test'));
        $client = $youtube->getHttpClient();

        $this->assertInstanceOf('Madcoda\Youtube\Http\CurlHttpClient', $client);

        // Changing it afterwards must reach the already-created transport.
        $youtube->setReferer('https://second.test');
        $this->assertSame($client, $youtube->getHttpClient());
    }
}
