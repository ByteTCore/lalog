# Changelog

All notable changes to `lalog` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-04-03

### Added

- Initial release
- SQL query logging with binding interpolation
- Query execution time tracking
- Configurable storage disk, directory, and file format
- File rotation when max size is exceeded
- Clear-on-start option for development workflow
- Support for Laravel 10.x, 11.x, and 12.x
- Facade support via `Lalog\Facades\Lalog`
- Config publishing via `vendor:publish --tag=lalog-config`
