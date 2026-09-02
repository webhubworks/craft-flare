# Release Notes for Craft Flare

## 2.0.2 - 2026-09-02
### Fixed
- Request context (URL, method, headers and body) reaches Flare again. `Flare::make()` only applies the client defaults when it is given an API token string, so passing a `FlareConfig` left every collector unregistered ([#2](https://github.com/webhubworks/craft-flare/issues/2)).
- Set the application path, so Git information is collected and stack frames are trimmed to the project.

### Added
- Added the `censorCookies` setting (defaults to `true`). Flare censors the `Cookie` header but reads the parsed cookies separately, so Craft's session ID and identity cookie were sent in the clear.
- Added `loginName` to the default `censorRequestBodyFields`. Craft's login form posts `loginName`, not `username`.
- Censored field names are matched up to two levels deep, so `email` now covers `fields[email]` as well. Use an explicit dot path for anything nested deeper.
- Credential fields (`CRAFT_CSRF_TOKEN`, `password`, `newPassword`, `currentPassword`, `account-password`, `loginName`) are censored whatever `censorRequestBodyFields` is set to, so projects that stored the setting before a field was added still do not post credentials.

### Changed
- Dump collection stays off. The recorder swaps the global `VarDumper` handler, and this plugin builds the client while an exception is already being handled, so it can never record a dump.
- Stack frame argument collection stays off. It sets `zend.exception_ignore_args` to 0 process-wide, and on a production ini the exception is created before the client boots, so the trace carries no arguments either way.

## 2.0.1 - 2026-06-21
### Added
- Added the `ignoredHttpStatusCodes` setting (defaults to `[403, 404]`) to configure which HTTP status codes are filtered out before reporting.

### Changed
- HTTP exceptions are now filtered by status code instead of exception class, so generic `HttpException(403|404)` throws from third-party code (e.g. verbb/wishlist) are filtered too, not just `ForbiddenHttpException` / `NotFoundHttpException`.

## 2.0.0 - 2026-04-13
### Changed
- Upgraded to `spatie/flare-client-php` v2.

## 1.3.3 - 2025-11-18
### Fixed
- Ensures error code is an integer.

## 1.3.2 - 2025-04-07
### Added
- Added queue handling exceptions.

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
