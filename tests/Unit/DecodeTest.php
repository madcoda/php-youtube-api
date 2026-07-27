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
            'empty body'  => array(''),
            'html error'  => array('<html><body>503 Service Unavailable</body></html>'),
            'truncated'   => array('{"items":['),
            'json null'   => array('null'),
            'json array'  => array('[1,2]'),
            'json scalar' => array('"a string"'),
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

    /**
     * A well-formed response that simply matched nothing. Distinct from a
     * malformed body: this is what a deleted video or an unmatched search
     * actually returns.
     */
    public function testDecodeSingleReturnsFalseWhenItemsIsEmpty()
    {
        $body = $this->fixture('videos.empty');

        $this->assertFalse($this->youtube->decodeSingle($body));
    }

    public function testDecodeListReturnsFalseWhenItemsIsEmpty()
    {
        $body = $this->fixture('search.empty');

        $this->assertFalse($this->youtube->decodeList($body));
    }

    public function testDecodeSingleReturnsTheFirstItem()
    {
        $body = $this->fixture('videos.multiple');
        $raw = $this->fixtureData('videos.multiple');

        $item = $this->youtube->decodeSingle($body);

        $this->assertSame($raw->items[0]->id, $item->id);
    }

    public function testDecodeListReturnsEveryItem()
    {
        $body = $this->fixture('videos.multiple');
        $raw = $this->fixtureData('videos.multiple');

        $items = $this->youtube->decodeList($body);

        $this->assertIsArray($items);
        $this->assertCount(count($raw->items), $items);
        $this->assertSame($raw->items[count($raw->items) - 1]->id, $items[count($items) - 1]->id);
    }

    public function testDecodeListPopulatesPageInfo()
    {
        $body = $this->fixture('search');
        $raw = $this->fixtureData('search');

        $this->youtube->decodeList($body);
        $info = $this->youtube->page_info;

        $this->assertSame($raw->kind, $info['kind']);
        $this->assertSame($raw->etag, $info['etag']);
        $this->assertSame($raw->pageInfo->resultsPerPage, $info['resultsPerPage']);
        $this->assertSame($raw->pageInfo->totalResults, $info['totalResults']);
        $this->assertSame($raw->nextPageToken, $info['nextPageToken']);
    }

    public function testDecodeListReadsBothPageTokens()
    {
        $body = $this->fixture('search.page2');
        $raw = $this->fixtureData('search.page2');

        $this->youtube->decodeList($body);
        $info = $this->youtube->page_info;

        $this->assertSame($raw->prevPageToken, $info['prevPageToken']);
        $this->assertSame($raw->nextPageToken, $info['nextPageToken']);
    }

    public function testPageTokensAreNullWhenAbsent()
    {
        $body = $this->fixture('channels.single');
        $this->youtube->decodeList($body);

        $this->assertNull($this->youtube->page_info['prevPageToken']);
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
        $this->assertNotNull($this->youtube->page_info['nextPageToken']);

        $bad = '<html>503</html>';
        $this->youtube->decodeList($bad);

        $this->assertNull($this->youtube->page_info['nextPageToken']);
        $this->assertNull($this->youtube->page_info['kind']);
        $this->assertNull($this->youtube->page_info['etag']);
    }

    public function testDecodeSingleThrowsOnAnApiError()
    {
        $body = $this->fixture('error.badkey');
        $raw = $this->fixtureData('error.badkey');

        $this->expectException('Exception');
        $this->expectExceptionCode($raw->error->code);
        $this->youtube->decodeSingle($body);
    }

    public function testApiErrorMessageCarriesCodeAndReason()
    {
        $body = $this->fixture('error.quota');
        $raw = $this->fixtureData('error.quota');

        try {
            $this->youtube->decodeSingle($body);
            $this->fail('Expected an exception for an API error body.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Error ' . $raw->error->code, $e->getMessage());
            $this->assertStringContainsString($raw->error->errors[0]->reason, $e->getMessage());
            $this->assertSame($raw->error->code, $e->getCode());
        }
    }

    public function testDecodeListThrowsOnAnApiError()
    {
        $body = $this->fixture('error.quota');

        $this->expectException('Exception');
        $this->expectExceptionCode(403);
        $this->youtube->decodeList($body);
    }
}
