<?php

namespace Madcoda\Youtube\Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Base test case.
 *
 * Extends the phpunit-polyfills test case so that one suite runs unchanged on
 * PHPUnit 6.5 (PHP 7.0) through PHPUnit 11 (PHP 8.5). Fixture methods are named
 * set_up()/tear_down() because PHPUnit 8 added a `void` return type to setUp()
 * and tearDown(), which older PHPUnit versions do not declare.
 */
abstract class TestCase extends PolyfillTestCase
{
    /**
     * Read a recorded API response.
     *
     * @param  string $name file name inside tests/fixtures, without .json
     * @return string       the raw JSON body
     */
    protected function fixture($name)
    {
        $path = __DIR__ . '/fixtures/' . $name . '.json';

        if (!file_exists($path)) {
            $this->fail('Missing fixture: ' . $path);
        }

        return file_get_contents($path);
    }
}
