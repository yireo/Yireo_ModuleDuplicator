# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.4] - 25 November 2026
### Added
- Support for PHP 8.5
- YireoTraining_Empty as a default template

### Changed
- Generated modules no longer carry the `dev` keyword in their composer.json
- Error messages now name the template that could not be found
- Console commands refuse to run in production mode

### Removed
- Support for PHP 8.1
- The LokiCheckout_Empty, LokiCheckout_EmptyPayment and LokiCheckout_EmptyShipment templates from the default template list

### Fixed
- Security: module names other than Vendor_Package are rejected, so a crafted name can no longer write files outside app/code or inject code into generated files
- Security: creating a module refuses an existing module or target folder, skips symlinks and checks every file write
- Generated files only get whole module identifiers (namespace, module name, composer name) replaced, instead of every occurrence of the vendor and package names
- update-composer-json no longer falls back to the root composer.json for unregistered modules, validates keywords and never writes an empty file on invalid JSON
- PHP 8.6 deprecation of trim() without explicit characters
- Missing symfony/console dependency

## [1.0.3] - 13 June 2026
### Fixed
- Bump symfony/finder to 7.0

## [1.0.2] - 22 October 2025
### Fixed
- Change Loki templates
- Fix license
- Make templates DI configurable

## [1.0.1] - 17 May 2025
### Fixed
- Remove hard dep with LokiChedckout
- Fix license

## [0.0.1] - 13 May 2025
### Added
- Initial release
