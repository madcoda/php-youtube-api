<?php

namespace Madcoda\Youtube\Http;

/**
 * The default transport, using ext-curl.
 *
 * This is the request code that previously lived inline in Youtube::api_get().
 */
class CurlHttpClient implements HttpClientInterface
{
    /**
     * @var string
     */
    protected $referer;

    /**
     * @var string
     */
    protected $sslPath;

    /**
     * @param string $referer
     * @param string $sslPath path to a CA bundle file or a CA directory
     */
    public function __construct($referer = null, $sslPath = null)
    {
        $this->referer = $referer;
        $this->sslPath = $sslPath;
    }

    /**
     * @param string $referer
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    /**
     * @param string $sslPath
     */
    public function setSslPath($sslPath)
    {
        $this->sslPath = $sslPath;
    }

    /**
     * {@inheritdoc}
     */
    public function get($url, array $params)
    {
        $tuCurl = curl_init();

        if ($this->sslPath !== null) {
            curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, 2);
            // CAPATH expects a directory, CAINFO a single bundle file.
            if (is_dir($this->sslPath)) {
                curl_setopt($tuCurl, CURLOPT_CAPATH, $this->sslPath);
            } else {
                curl_setopt($tuCurl, CURLOPT_CAINFO, $this->sslPath);
            }
        }

        curl_setopt($tuCurl, CURLOPT_URL, $this->buildUrl($url, $params));

        if ($this->referer !== null) {
            curl_setopt($tuCurl, CURLOPT_REFERER, $this->referer);
        }

        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, true);

        $tuData = curl_exec($tuCurl);
        if (curl_errno($tuCurl)) {
            throw new \Exception('Curl Error : ' . curl_error($tuCurl), curl_errno($tuCurl));
        }

        return $tuData;
    }

    /**
     * Append the query string to the endpoint.
     *
     * An endpoint that already carries a query string needs '&', not '?'. The
     * previous inline version appended nothing at all in that case, running the
     * existing query straight into the first new parameter.
     *
     * @param  string $url
     * @param  array  $params
     * @return string
     */
    protected function buildUrl($url, array $params)
    {
        $query = http_build_query($params);
        if ($query === '') {
            return $url;
        }

        if (strpos($url, '?') === false) {
            $separator = '?';
        } elseif (substr($url, -1) === '?' || substr($url, -1) === '&') {
            $separator = '';
        } else {
            $separator = '&';
        }

        return $url . $separator . $query;
    }
}
