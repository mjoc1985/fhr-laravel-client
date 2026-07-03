<?php

namespace Mjoc1985\Fhr\Services;

use Illuminate\Support\Collection;
use Mjoc1985\Fhr\Data\FhrBookingData;
use Mjoc1985\Fhr\Data\FhrOrderResponseData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\FhrClient;

/**
 * Partner/affiliate booking-management endpoints (`/partner/*`).
 *
 * These are the raw provider calls for looking up, listing, cancelling, and
 * reconciling bookings. Persistence and orchestration (updating your own
 * models, dispatching events) belong in the consuming application on top of
 * these calls.
 */
class FhrPartnerService
{
    public function __construct(
        private readonly FhrClient $client,
    ) {}

    public static function make(bool $sandbox = false): self
    {
        return new self(FhrClient::make($sandbox));
    }

    /**
     * Partner order details by GUID.
     *
     * `GET /partner/order/details/{guid}`. If the order is not yet successful,
     * FHR returns only status and total.
     *
     * @return array<string, mixed>
     */
    public function getOrderDetails(string $guid): array
    {
        return $this->client->get("partner/order/details/{$guid}");
    }

    /**
     * List a customer's bookings by email.
     *
     * `POST /partner/bookings`.
     *
     * @return Collection<int, FhrBookingData>
     */
    public function getBookingsByEmail(string $email, FhrProductType $type = FhrProductType::Parking): Collection
    {
        $response = $this->client->post('partner/bookings', [
            'email' => $email,
            'type' => $type->value,
        ]);

        // Prefer an explicit `bookings` key; otherwise only treat the response
        // as the booking list when it is a plain array, so a wrapped or
        // metadata-bearing object is not mis-mapped into a single booking.
        $bookings = $response['bookings'] ?? (array_is_list($response) ? $response : []);

        return collect($bookings)
            ->filter(fn ($booking) => is_array($booking))
            ->map(fn (array $booking) => FhrBookingData::from($booking))
            ->values();
    }

    /**
     * Full booking details.
     *
     * `POST /partner/booking/details`.
     *
     * @return array<string, mixed>
     */
    public function getBookingDetails(
        string $bookingId,
        FhrProductType $type = FhrProductType::Parking,
        ?string $email = null,
        string $currency = 'GBP',
        string $lang = 'en-gb',
    ): array {
        return $this->client->post('partner/booking/details', array_filter([
            'bookingId' => $bookingId,
            'type' => $type->value,
            'email' => $email,
            'currency' => $currency,
            'lang' => $lang,
        ], fn ($value) => $value !== null));
    }

    /**
     * Check whether a booking is eligible for cancellation.
     *
     * `POST /partner/booking/can-cancel`.
     *
     * @return array<string, mixed>
     */
    public function canCancel(
        string $bookingId,
        string $email,
        FhrProductType $type = FhrProductType::Parking,
    ): array {
        return $this->client->post('partner/booking/can-cancel', [
            'bookingId' => $bookingId,
            'email' => $email,
            'type' => $type->value,
        ]);
    }

    /**
     * Cancel an FHR booking.
     *
     * `DELETE /partner/booking/cancel`. Normalises FHR's varied cancellation
     * responses (including "already cancelled" broken-rules) into a single
     * success flag.
     */
    public function cancelBooking(
        string $bookingId,
        string $email,
        FhrProductType $type = FhrProductType::Parking,
    ): FhrOrderResponseData {
        $response = $this->client->delete('partner/booking/cancel', [
            'bookingId' => $bookingId,
            'type' => $type->value,
            'email' => $email,
        ]);

        $status = $response['status'] ?? '';
        $brokenRules = $response['brokenRules'] ?? [];
        $alreadyCancelled = collect($brokenRules)->contains(
            fn ($rule) => is_array($rule) && stripos($rule['rule'] ?? '', 'already cancelled') !== false,
        );
        $isCancelled = stripos($status, 'cancelled') !== false || $alreadyCancelled;

        return FhrOrderResponseData::from([
            'success' => $response['success'] ?? $isCancelled,
            'order' => $response['order'] ?? $bookingId,
            'message' => $response['message'] ?? $status,
            'error' => $response['error'] ?? null,
            'paidAmount' => isset($response['paidAmount']) ? (float) $response['paidAmount'] : null,
            'refundAmount' => isset($response['refundAmount']) ? (float) $response['refundAmount'] : null,
        ]);
    }

    /**
     * Financial data (commissions, margins) for a booking.
     *
     * `POST /partner/booking/financial`. The `source` must be in the
     * authenticated partner's authorised sources.
     *
     * @return array<string, mixed>
     */
    public function getBookingFinancial(
        string $source,
        string $bookingId,
        string $email,
        FhrProductType $type = FhrProductType::Parking,
    ): array {
        return $this->client->post('partner/booking/financial', [
            'source' => $source,
            'bookingId' => $bookingId,
            'type' => $type->value,
            'email' => $email,
        ]);
    }

    /**
     * Confirm all items in an order with the provider after payment.
     *
     * `POST /partner/booking/confirm`.
     *
     * @return array<string, mixed>
     */
    public function confirmWithPaymentRef(string $orderId, string $paymentRef): array
    {
        return $this->client->post('partner/booking/confirm', [
            'orderId' => $orderId,
            'paymentRef' => $paymentRef,
        ]);
    }

    /**
     * Full product information from FHR.
     *
     * `GET /partner/product/{id}`.
     *
     * @return array<string, mixed>
     */
    public function getProduct(int $productId): array
    {
        return $this->client->get("partner/product/{$productId}");
    }
}
