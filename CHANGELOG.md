# Release Notes for Craft Flare

## 1.3.1 - 2025-03-11
### Changed
- Improved reporting of fatal PHP errors.

## 1.3.0 - 2025-03-11
### Added
- Now also reporting fatal PHP errors.

## 1.2.1 - 2025-02-07
### Changed
- Now also filtering out `ForbiddenHttpException` and `{% exit 403 %}` Twig statements.

## 1.2.0 - 2025-01-29
### Added
- Ability to test error reporting via dedicated buttons on the settings page.

### Changed
- No longer reporting `{% exit 404 %}` Twig statements, aligning with the existing filtering of `NotFoundHttpException`.

## 1.1.1 - 2024-12-16
### Fixed
- Fixed exception when Flare key was not provided

## 1.1.0 - 2024-11-20
### Added
- Ability to enable censoring queries

## 1.0.6 - 2024-11-08
### Added
- Ability to access the Flare instance via `CraftFlare::getFlareInstance()`

## 1.0.5 - 2024-11-07
### Fixed
- IP addresses in the request header were not correctly censored

## 1.0.4 - 2024-11-07
### Changed
- Refactoring to use a FlareService and gather system data after the Craft app is initialized 

## 1.0.3 - 2024-10-24
### Fixed
- Catching non-initialized Flare instance 

## 1.0.2 - 2024-10-24
### Added
- Added changelog

## 1.0.1 - 2024-10-24
### Fixed
- Accessing user data before init

## 1.0.0 - 2024-10-18
- Initial release
