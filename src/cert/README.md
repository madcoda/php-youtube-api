# Deprecated CA bundle

`cacert.pem` here is **deprecated and no longer used by the library**. It will be removed
in 2.0.

It dates from 2013 and contains roots that have since expired or been distrusted. Until
recently it was worse than unused: the `$sslPath` constructor argument was accepted but
never applied, and `CURLOPT_CAINFO` and `CURLOPT_CAPATH` were hardcoded to this file, so
passing *any* value silently replaced the system trust store with this snapshot.

The library now leaves certificate verification to curl's defaults, which use the
operating system's trust store. That is almost always what you want, and it stays current
without anyone updating a file in this repository.

If you do need a specific bundle, pass its path explicitly:

```php
// a bundle file
new Madcoda\Youtube\Youtube(array('key' => '...'), '/etc/ssl/certs/ca-certificates.crt');

// or a directory of hashed certificates
new Madcoda\Youtube\Youtube(array('key' => '...'), '/etc/ssl/certs');
```

A path that does not exist now raises `InvalidArgumentException` at construction, rather
than quietly falling back to the wrong certificates.
