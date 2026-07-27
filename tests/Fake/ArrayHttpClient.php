<?php

namespace Madcoda\Youtube\Tests\Fake;

use Madcoda\Youtube\Http\HttpClientInterface;

/**
 * A transport that returns canned bodies and records what it was asked for.
 *
 * Lets the suite assert on the request the library builds (endpoint, part,
 * pagination, the injected API key) as well as on what it does with the
 * response, without touching the network.
 */
class ArrayHttpClient implements HttpClientInterface
{
    /**
     * Every request made, in order. Each entry is array('url' => .., 'params' => ..).
     *
     * @var array
     */
    public $calls = array();

    /**
     * Bodies to return, consumed in order.
     *
     * @var array
     */
    protected $queue = array();

    /**
     * Returned once the queue is exhausted.
     *
     * @var string
     */
    protected $fallback;

    /**
     * Thrown instead of returning, to simulate a transport failure.
     *
     * @var \Exception
     */
    protected $throw = null;

    /**
     * @param string|array $bodies one body, or a list returned in order
     */
    public function __construct($bodies = array())
    {
        if (!is_array($bodies)) {
            $bodies = array($bodies);
        }

        $this->queue = $bodies;
        $this->fallback = count($bodies) ? $bodies[count($bodies) - 1] : '{}';
    }

    /**
     * Add another body to the end of the queue.
     *
     * @param  string $body
     * @return $this
     */
    public function queue($body)
    {
        $this->queue[] = $body;
        $this->fallback = $body;

        return $this;
    }

    /**
     * Make the next request fail at the transport level.
     *
     * @param  \Exception $e
     * @return $this
     */
    public function willThrow(\Exception $e)
    {
        $this->throw = $e;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($url, array $params)
    {
        $this->calls[] = array('url' => $url, 'params' => $params);

        if ($this->throw !== null) {
            $e = $this->throw;
            $this->throw = null;
            throw $e;
        }

        if (count($this->queue)) {
            return array_shift($this->queue);
        }

        return $this->fallback;
    }

    /**
     * @return int
     */
    public function callCount()
    {
        return count($this->calls);
    }

    /**
     * @param  int   $i index, defaulting to the most recent call
     * @return array array('url' => .., 'params' => ..)
     */
    public function call($i = -1)
    {
        if ($i < 0) {
            $i = count($this->calls) - 1;
        }

        return $this->calls[$i];
    }

    /**
     * @param  int    $i
     * @return string
     */
    public function url($i = -1)
    {
        $call = $this->call($i);

        return $call['url'];
    }

    /**
     * @param  int   $i
     * @return array
     */
    public function params($i = -1)
    {
        $call = $this->call($i);

        return $call['params'];
    }
}
