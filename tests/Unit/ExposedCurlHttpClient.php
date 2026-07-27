<?php

namespace Madcoda\Youtube\Tests\Unit;

use Madcoda\Youtube\Http\CurlHttpClient;

/**
 * Makes CurlHttpClient's protected URL builder reachable from tests without
 * reflection, which behaves inconsistently across the supported PHP range.
 */
class ExposedCurlHttpClient extends CurlHttpClient
{
    /**
     * @param  string $url
     * @param  array  $params
     * @return string
     */
    public function publicBuildUrl($url, array $params)
    {
        return $this->buildUrl($url, $params);
    }
}
