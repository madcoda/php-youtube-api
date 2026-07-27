<?php

namespace Madcoda\Youtube\Http;

/**
 * Transport used to talk to the Youtube Data API.
 *
 * This exists so the library can be exercised without a network connection or
 * an API key. Implement it to route requests through your own HTTP stack, or
 * to return recorded responses in a test suite.
 */
interface HttpClientInterface
{
    /**
     * Issue a GET request and return the raw, undecoded response body.
     *
     * @param  string $url    the endpoint, without a query string
     * @param  array  $params query parameters to append
     * @throws \Exception     on any transport-level failure
     * @return string         the raw response body
     */
    public function get($url, array $params);
}
