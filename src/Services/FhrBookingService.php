<?php

namespace Mjoc1985\Fhr\Services;

use Illuminate\Support\Facades\Log;
use Mjoc1985\Fhr\Data\FhrCustomerData;
use Mjoc1985\Fhr\Data\FhrOrderResponseData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\FhrClient;

/**
 * Synchronous ("legacy"/invoice) booking flow.
 *
 * Creates an internal cart, books with FHR, and confirms in a single call,
 * without the async cart/Stripe flow. Intended for B2B / invoice integrations.
 */
class FhrBookingService
{
    public function __construct(
        private readonly FhrClient $client,
    ) {}

    public static function make(bool $sandbox = false): self
    {
        return new self(FhrClient::make($sandbox));
    }

    /**
     * Create and confirm a booking in one call (payment method: invoice).
     *
     * `POST /booking` — returns `{ success, order }`.
     *
     * @param  array<int, array{flightNumber?: string, direction?: string, terminal?: string}>  $flights
     *                                                                                                    Flight legs. `direction` must be lowercase "outbound" or "inbound".
     * @param  array<string, mixed>|null  $vehicle  Vehicle details (reg, make, model, colour, passengers).
     */
    public function book(
        string $searchId,
        FhrProductType $type,
        string $productId,
        string $optionId,
        FhrCustomerData $customer,
        array $flights = [],
        ?array $vehicle = null,
    ): FhrOrderResponseData {
        $response = $this->client->post('booking', $this->buildPayload(
            $searchId, $type, $productId, $optionId, $customer, $flights, $vehicle
        ));

        return FhrOrderResponseData::from([
            'success' => $response['success'] ?? false,
            'order' => $response['order'] ?? null,
            'message' => $response['message'] ?? null,
            'error' => $response['error'] ?? null,
        ]);
    }

    /**
     * Same as {@see self::book()} but returns the full order detail response.
     *
     * `POST /booking-create` — returns `{ success, status, total, order, items }`.
     *
     * @param  array<int, array{flightNumber?: string, direction?: string, terminal?: string}>  $flights
     * @param  array<string, mixed>|null  $vehicle
     * @return array<string, mixed>
     */
    public function bookWithDetails(
        string $searchId,
        FhrProductType $type,
        string $productId,
        string $optionId,
        FhrCustomerData $customer,
        array $flights = [],
        ?array $vehicle = null,
    ): array {
        return $this->client->post('booking-create', $this->buildPayload(
            $searchId, $type, $productId, $optionId, $customer, $flights, $vehicle
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $flights
     * @param  array<string, mixed>|null  $vehicle
     * @return array<string, mixed>
     */
    private function buildPayload(
        string $searchId,
        FhrProductType $type,
        string $productId,
        string $optionId,
        FhrCustomerData $customer,
        array $flights,
        ?array $vehicle,
    ): array {
        Log::channel(config('fhr.log_channel'))->info('Creating FHR synchronous booking', [
            'search_id' => $searchId,
            'type' => $type->value,
            'product_id' => $productId,
        ]);

        return [
            'booking' => [
                'searchId' => $searchId,
                'type' => $type->value,
                'productId' => $productId,
                'optionId' => $optionId,
            ],
            'customer' => $customer->toArray(),
            'params' => [
                'flights' => $flights,
                'vehicle' => $vehicle,
            ],
        ];
    }
}
