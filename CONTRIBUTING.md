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

## Adding a fixture

Fixtures are the raw response body, exactly as the API returns it. Add the JSON file to
`tests/fixtures/` and load it with `$this->fixture('name')` (no `.json` suffix).

## Reporting a bug

Please include the PHP version, the library version, and the failing call. If the API
returned an error, the exception message carries Google's error code and reason, which
is the most useful thing to paste.
