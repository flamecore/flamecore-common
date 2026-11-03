# FlameCore Common

[![Latest Stable](http://info.iceflame.dev/flamecore/flamecore-common/version.svg)](http://info.iceflame.dev/flamecore/flamecore-common/latest)
[![Build Status](http://info.iceflame.dev/flamecore/flamecore-common/health.svg)](http://info.iceflame.dev/flamecore/flamecore-common/health)
[![Code Quality](http://info.iceflame.dev/flamecore/flamecore-common/quality.svg)](http://info.iceflame.dev/flamecore/flamecore-common/quality)
[![Coverage](http://info.iceflame.dev/flamecore/flamecore-common/coverage.svg)](http://info.iceflame.dev/flamecore/flamecore-common/coverage)
[![License](http://info.iceflame.dev/flamecore/flamecore-common/license.svg)](http://info.iceflame.dev/flamecore/flamecore-common/license)

This package contains a set of lightweight utilities for common use cases.

It provides the following extensions to core PHP functionality:

- **Contracts** – Some common interfaces for interoperability
- **Exceptions** – A common exception hierarchy (`ExceptionInterface`)
- Class **Strings** – Many useful string and text functions
- Class **Arrays** – Functions to check and manipulate arrays
- Class **Arrays\ArrayEntry** – Access nested array entries by key or path
- Class **Callback** – PHP callback related functions
- Trait **StaticClass** – Create purely static classes


## Installation

### Install via Composer

[Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos) if you don't already have it present on your system.

To install the library, run the following command and you will get the latest version:

    $ composer require flamecore/common


## Requirements

- You must have at least PHP version 8.0 installed on your system.
- Recommended: the `mbstring`, `iconv` and `intl` extensions for full Unicode support in `Strings`.


## Security

If you discover any security related issues, please email security@flamecore.org instead of using the issue tracker.


## Credits

- [Nette Framework team and contributors](https://nette.org)
- [Christian Neff](https://www.secondtruth.de)
- [All Contributors](https://github.com/flamecore/flamecore-common/graphs/contributors)
