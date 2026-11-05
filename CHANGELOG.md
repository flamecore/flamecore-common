# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [Unreleased]


## [1.0.0] - YYYY-MM-DD

First stable release. Requires PHP 8.0 or later; tested on PHP 8.0 to 8.5.

### Added

- `Strings` – Unicode-aware string functions (length, substring, trim, truncate, case conversion,
  normalization, UTF-8 validation/repair, ISO-8859-1 conversion) and safe PCRE wrappers
  (`split`, `match`, `matchAll`, `replace`, `replaceDynamically`)
- `Arrays` – Functions to check and inspect arrays (`contains`, `first`, `last`, `keyExists`,
  `isList`, `isAssociative`, `some`, `all`, …)
- `Arrays\ArrayEntry` – Read, write and remove nested array entries by key or dotted path
- `Callback` – Callback helpers (`invokeSafe`, `toString`, `toReflection`, `isStatic`, `unwrap`)
- `StaticClass` trait – Prevents instantiation of purely static classes
- `Contracts\Arrayable` and `Contracts\Restorable` interfaces
- Exception hierarchy under `Exception\` with the common `ExceptionInterface`


[Unreleased]: https://github.com/flamecore/flamecore-common/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/flamecore/flamecore-common/releases/tag/v1.0.0
