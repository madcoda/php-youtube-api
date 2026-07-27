<?php

namespace Madcoda\Youtube\Tests\Unit;

use Madcoda\Youtube\Tests\TestCase;
use Madcoda\Youtube\Youtube;

/**
 * Response decoding, including the malformed bodies that used to be fatal on
 * PHP 8. A 5xx from Google is frequently an HTML page rather than JSON, so
 * these paths are reached in normal operation, not just in theory.
 */
class DecodeTest extends TestCase
{
    /**
     * @var Youtube
     */
    protected $youtube;

    protected function set_up()
    {
        $this->youtube = new Youtube(array('key' => 'TESTKEY'));
    }

    public static function undecodableBodyProvider()
    {
        return array(
            'empty body'    => array(''),
            'html error'    => array('<html><body>503 Service Unavailable</body></html>'),
            'truncated'     => array('{"items":['),
            'json null'     => array('null'),
            'json array'    => array('[1,2]'),
            'json scalar'   => array('"a string"'),
        );
    }

    /**
     * decodeSingle() previously passed the decoded value straight to
     * property_exists(), which is a TypeError on PHP 8 when the body did not
     * decode to an object.
     *
     * @dataProvider undecodableBodyProvider
     */
    public function testDecodeSingleReturnsFalseForAnUndecodableBody($body)
    {
        $this->assertFalse($this->youtube->decodeSingle($body));
    }

    /**
     * @dataProvider undecodableBodyProvider
     */
    public function testDecodeListReturnsFalseForAnUndecodableBody($body)
    {
        $this->assertFalse($this->youtube->decodeList($body));
    }

    public function testDecodeSingleReturnsFalseWhenItemsIsEmpty()
    {
        $body = $this->fixture('search.empty');

        $this->assertFalse($this->youtube->decodeSingle($body));
    }

    public function testDecodeSingleReturnsTheFirstItem()
    {
        $body = $this->fixture('videos.multiple');
        $item = $this->youtube->decodeSingle($body);

        $this->assertSame('rie-hPVJ7Sw', $item->id);
    }

    public function testDecodeListReturnsEveryItem()
    {
        $body = $this->fixture('videos.multiple');
        $items = $this->youtube->decodeList($body);

        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $this->assertSame('lRRk97FYLJM', $items[1]->id);
    }

    public function testDecodeListPopulatesPageInfo()
    {
        $body = $this->fixture('search');
        $this->youtube->decodeList($body);

        $this->assertSame('youtube#searchListResponse', $this->youtube->page_info['kind']);
        $this->assertSame('etag-search', $this->youtube->page_info['etag']);
        $this->assertSame(3, $this->youtube->page_info['resultsPerPage']);
        $this->assertSame(1000000, $this->youtube->page_info['totalResults']);
        $this->assertSame('CAUQAA', $this->youtube->page_info['nextPageToken']);
        $this->assertNull($this->youtube->page_info['prevPageToken']);
    }

    public function testDecodeListReadsPrevPageToken()
    {
        $body = $this->fixture('search.page2');
        $this->youtube->decodeList($body);

        $this->assertSame('CAUQAQ', $this->youtube->page_info['prevPageToken']);
        $this->assertNull($this->youtube->page_info['nextPageToken']);
    }

    /**
     * A failed decode must not leave the caller reading pagination data from
     * the previous, successful request.
     */
    public function testPageInfoIsResetAfterAFailedDecode()
    {
        $good = $this->fixture('search');
        $this->youtube->decodeList($good);
        $this->assertSame('CAUQAA', $this->youtube->page_info['nextPageToken']);

        $bad = '<html>503</html>';
        $this->youtube->decodeList($bad);

        $this->assertNull($this->youtube->page_info['nextPageToken']);
        $this->assertNull($this->youtube->page_info['kind']);
        $this->assertNull($this->youtube->page_info['etag']);
    }

    public function testDecodeSingleThrowsOnAnApiError()
    {
        $body = $this->fixture('error.quota');

        $this->expectException('Exception');
        $this->expectExceptionCode(403);
        $this->youtube->decodeSingle($body);
    }

    public function testApiErrorMessageIncludesCodeAndReason()
    {
        $body = $this->fixture('error.quota');

        try {
            $this->youtube->decodeSingle($body);
            $this->fail('Expected an exception for an API error body.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Error 403', $e->getMessage());
            $this->assertStringContainsString('quotaExceeded', $e->getMessage());
        }
    }

    public function testDecodeListThrowsOnAnApiError()
    {
        $body = $this->fixture('error.badkey');

        $this->expectException('Exception');
        $this->expectExceptionCode(400);
        $this->youtube->decodeList($body);
    }
}
