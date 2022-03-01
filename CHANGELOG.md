# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [4.1.0] - 2022-03-01
### Added
- Support for PHTML files [#7].

## [4.0.1] - 2021-12-16
### Fixed
- Support for PHP 8

## [4.0.0] - 2019-11-05
### Added
- Ability to scan and save multiple domains at the same time

### Changed
- Upgraded gettext/gettext to 5.0
- API changes:
  - Replaced `extract()` with `scan()`
  - Replaced `generate()` with `save()`

[#7]: https://github.com/php-gettext/Robo/issues/7

[4.1.0]: https://github.com/php-gettext/Robo/compare/v4.0.1...v4.1.0
[4.0.1]: https://github.com/php-gettext/Robo/compare/v4.0.0...v4.0.1
[4.0.0]: https://github.com/php-gettext/Robo/releases/tag/v4.0.0
