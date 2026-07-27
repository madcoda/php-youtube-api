# Contributing

Thanks for helping out.

## Getting set up

```sh
composer install
vendor/bin/phpunit
```

The default suite runs offline against recorded fixtures in `tests/fixtures`, so it
needs no API key and consumes no quota.

## Running the live tests

A second suite talks to the real Youtube Data API, to catch changes in Google's
response shapes. It is excluded from the default run and skips cleanly without a key:

```sh
YOUTUBE_API_KEY=your-key vendor/bin/phpunit --group live
```

## Before opening a pull request

```sh
# style, PSR-12
php-cs-fixer fix --dry-run --diff

# static analysis
phpstan analyse
```

PHP-CS-Fixer and PHPStan are intentionally *not* dev dependencies: both require a PHP
far newer than this library's floor, and adding them to `composer.json` would make
`composer install` unsatisfiable on PHP 7.0. Install them globally, or as PHARs.

## The PHP 7.0 floor

This is the constraint that shapes most decisions here. The 1.x line supports PHP 7.0
through 8.5, and CI runs all eleven versions. Practical consequences:

- **No syntax newer than PHP 7.0.** No nullable types (`?string`), no `void` return
  types, no typed properties, no arrow functions, no constructor promotion, no
  `public const`. The `lint` CI job runs `php -l` on 7.0 to catch these.
- **Long array syntax.** `[]` would in fact be safe on 7.0, but the codebase is
  consistent and rewriting every array would bury real changes in noise.
- **Test code uses annotations, not attributes.** `#[DataProvider]` needs PHP 8 and
  PHPUnit 10+. PHPUnit 11 reports the doc-comment form as deprecated; that is expected
  and is why the constraint stops short of PHPUnit 12, which removes it.
- **No `composer.lock`.** Each PHP version must be free to resolve the PHPUnit major
  that supports it, from 5.7 on PHP 7.0 to 11.5 on PHP 8.5.
- Cross-version assertions come from
  [`yoast/phpunit-polyfills`](https://github.com/Yoast/PHPUnit-Polyfills). Use
  `set_up()` / `tear_down()` rather than `setUp()` / `tearDown()`, since PHPUnit 8
  added a `void` return type that older versions do not declare.

If a change genuinely needs newer syntax, say so in the pull request. It is a
reasonable thing to want, but it makes the change 2.0 material rather than 1.x.

## Fixtures

`tests/fixtures/*.json` are real API responses, captured from live calls. Do not
hand-write them. Re-record with:

```sh
YOUTUBE_API_KEY=your-key php tests/tools/record-fixtures.php
```

That costs roughly 310 quota units, almost all of it the three search calls at 100 each.

Load one in a test with `$this->fixture('name')` for the raw body, or
`$this->fixtureData('name')` for the decoded object.

**Assert Google's values against the fixture, not against literals.** Anything that
originates at the API — ids, page tokens, item counts — should be compared with
`$this->fixtureData(...)` so that a re-record does not break the suite. The assertion
stays meaningful: comparing against `$raw->items[0]->id` still catches `decodeSingle()`
returning the wrong item. Values the *library* produces — endpoint URLs, parameter
names, the injected key — stay as literals, because those are the thing under test.

`error.quota.json` is the one exception and is hand-written: deliberately exhausting the
daily quota to record it is not something the recorder should do.

`FixtureDriftTest` (in the `live` group) compares every fixture's structure against a
fresh live response and fails when the API has stopped returning a field the fixture
records. That is the signal to re-record. New fields appearing live are reported as a
note rather than a failure, since they break nothing.

## Test subjects

Real videos, channels and playlists live in `tests/Subjects.php`, not scattered through
the tests. When Google removes one, that is the only file to edit. Pick replacements for
durability: institutional channels over personal ones, official uploads over copies, and
the uploads playlist (`UU...`) over a curated playlist, since the uploads playlist cannot
be deleted while the channel exists.

## Reporting a bug

Please include the PHP version, the library version, and the failing call. If the API
returned an error, the exception message carries Google's error code and reason, which
is the most useful thing to paste.
