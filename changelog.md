# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Add phpstan analysis
### Changed
- Support for SF 4.3
- Enforce fopen "b" mode in CS checks
### Deprecated
### Fixed
- Make sure Symfony Messenger v4.2 is installed
### Removed
### Security

## [0.1.3]
### Changed
- Support for SF 4.2
### Fixed
- Fixed issue with fopen modes (thanks to @karser)

## [0.1.2]
### Added
- Added GitAttributes file (ignore tests in packaged library)
### Fixed
- Fixed wrong version for `symfony/serializer-pack` component (closes issue #2)

## [0.1.1]
### Added
- Reference to Symfony bundle "pnz/messenger-filesystem-transport-bundle"
### Fixed
- Added missing package "symfony/serializer-pack"

## [0.1.0]
### Added
- Add PHP-CS and preliminary tests (integration with Travis)
- Add `loop_sleep` configuration parameter
- Add `compress` configuration parameter
- First implementation
