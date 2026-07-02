# Changelog

All notable changes to `mjoc1985/fhr-client` will be documented in this file.

## [Unreleased]

### Added
- Initial extraction from the ParkRight application.
- `FhrClient` with retry, client-side rate limiting, and pluggable API logging.
- `FhrSearchService`, `FhrInventoryService`, `FhrCartService`.
- DTOs (`Mjoc1985\Fhr\Data\*`), enums (`Mjoc1985\Fhr\Enums\*`), and exceptions (`Mjoc1985\Fhr\Exceptions\*`).
- `ApiLogger` contract with a `NullApiLogger` default so the client works with no wiring.
- Auto-discovered `FhrServiceProvider` and publishable `config/fhr.php`.
