# MediaWiki decompress.php

v0.1.0

Copyright 2014 Nic Jansma

http://nicj.net

Licensed under the MIT license

## Introduction

Decompresses a MediaWiki text table that previously had `$wgCompressRevisions = true` set.

I've used this to decompress the text table so [Sphinx](http://sphinxsearch.com/) would work.

## Usage

Put in `maintenance\storage`, then run:
```
php -f maintenance\storage\decompress.php
```

No warranty :)

## Version History

* v0.1.0 - 2014-12-13: Initial version

