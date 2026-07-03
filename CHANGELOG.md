# Changelog

All notable changes to `mjoc1985/fhr-laravel-client` will be documented in this file.

## [Unreleased]

### Added
- **Tier 1 endpoint coverage** (from the DOOYER API spec):
  - `FhrSearchService::getSearchById()` — `GET /search/{id}` (retrieve cached results).
  - `FhrSearchService::checkSearch()` — `GET /search-check/{id}`, with 410 normalised to an expired `FhrSearchValidityData`.
  - `FhrCartService::getCartItems()` — `GET /cart/{id}` (list items).
  - `FhrCartService::validateOrder()` — `POST /cart/{id}/order/validate` (pre-booking availability check).
  - `FhrBookingService` — `POST /booking` and `POST /booking-create` (synchronous / invoice booking).
  - `FhrOrderService::getOrderDetails()` — `GET /order/details/{guid}`.
- **Tier 2 partner / booking-management** (`FhrPartnerService`):
  - `getOrderDetails()` — `GET /partner/order/details/{guid}`.
  - `getBookingsByEmail()` — `POST /partner/bookings` (returns `FhrBookingData`).
  - `getBookingDetails()` — `POST /partner/booking/details`.
  - `canCancel()` — `POST /partner/booking/can-cancel`.
  - `cancelBooking()` — `DELETE /partner/booking/cancel` (normalises already-cancelled responses).
  - `getBookingFinancial()` — `POST /partner/booking/financial`.
  - `confirmWithPaymentRef()` — `POST /partner/booking/confirm`.
  - `getProduct()` — `GET /partner/product/{id}`.
- Initial release as a standalone package.
- `FhrClient` with retry, client-side rate limiting, and pluggable API logging.
- `FhrSearchService`, `FhrInventoryService`, `FhrCartService`.
- DTOs (`Mjoc1985\Fhr\Data\*`), enums (`Mjoc1985\Fhr\Enums\*`), and exceptions (`Mjoc1985\Fhr\Exceptions\*`).
- `ApiLogger` contract with a `NullApiLogger` default so the client works with no wiring.
- Auto-discovered `FhrServiceProvider` and publishable `config/fhr.php`.
