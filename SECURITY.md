# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes       |
| < 1.2   | No        |

## Reporting a vulnerability

Please report security issues privately to **jason@madcoda.com** rather than opening a
public issue. Include the affected version, the PHP version, and a description of the
impact. You can expect an acknowledgement within a few days.

## Scope notes

This library sends an API key as a query parameter, which is what the Youtube Data API
requires. Keep keys out of version control, restrict them by referrer or IP in the
Google API Console, and rotate them if they leak.

TLS certificate verification is left to curl's defaults, which use the system trust
store. The optional second constructor argument overrides that with a CA bundle file or
directory of your choosing:

```php
new Madcoda\Youtube\Youtube(array('key' => '...'), '/etc/ssl/certs/ca-certificates.crt');
```

The CA bundle vendored at `src/cert/cacert.pem` is deprecated, no longer referenced, and
dates from 2013. Do not point new code at it.
