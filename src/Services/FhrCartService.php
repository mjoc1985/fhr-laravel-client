<?php

namespace Mjoc1985\Fhr\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mjoc1985\Fhr\Data\FhrCartData;
use Mjoc1985\Fhr\Data\FhrCartItemData;
use Mjoc1985\Fhr\Data\FhrCartTotalsData;
use Mjoc1985\Fhr\Data\FhrCheckoutData;
use Mjoc1985\Fhr\Data\FhrCustomerData;
use Mjoc1985\Fhr\Data\FhrFlightData;
use Mjoc1985\Fhr\Data\FhrOrderResponseData;
use Mjoc1985\Fhr\Data\FhrPassengerData;
use Mjoc1985\Fhr\Data\FhrVehicleData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\FhrClient;
use Spatie\LaravelData\DataCollection;

class FhrCartService
{
    public function __construct(
        private readonly FhrClient $client,
    ) {}

    public static function make(bool $sandbox = false): self
    {
        return new self(FhrClient::make($sandbox));
    }

    /**
     * Create a new cart.
     */
    public function createCart(string $currency = 'GBP'): FhrCartData
    {
        $response = $this->client->post('cart/create', [
            'currency' => $currency,
        ]);

        $cartId = $response['cartId'];

        return new FhrCartData(
            cartId: $cartId,
            items: new DataCollection(FhrCartItemData::class, []),
            totals: null,
            currency: $currency,
        );
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(
        string $cartId,
        string $searchId,
        string $productId,
        string $optionId,
        FhrProductType $productType,
        int $quantity = 1,
    ): FhrCartData {
        $response = $this->client->post("cart/{$cartId}", [
            'searchId' => $searchId,
            'product' => $productId,
            'option' => $optionId,
            'type' => $productType->value,
            'qty' => $quantity,
        ]);

        return FhrCartData::fromApiResponse($response, $cartId);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(string $cartId, string $itemId): FhrCartData
    {
        $response = $this->client->post("cart/{$cartId}/remove", [
            'itemId' => $itemId,
        ]);

        return FhrCartData::fromApiResponse($response, $cartId);
    }

    /**
     * Get full cart with items and totals.
     */
    public function getCart(string $cartId): FhrCartData
    {
        $response = $this->client->get("cart/{$cartId}/totals");

        return FhrCartData::fromApiResponse($response, $cartId);
    }

    /**
     * Get cart totals.
     */
    public function getTotals(string $cartId): FhrCartTotalsData
    {
        $response = $this->client->get("cart/{$cartId}/totals");

        return FhrCartTotalsData::fromApiResponse($response['totals'] ?? []);
    }

    /**
     * Submit the cart order.
     *
     * @param  string|null  $itemId  The cart item ID (from addItem response). If null, falls back to sequential IDs.
     */
    public function submitOrder(
        string $cartId,
        FhrCustomerData $customer,
        array $products,
        ?string $itemId = null,
    ): FhrOrderResponseData {
        $productData = collect($products)->map(function (array $product, int $index) use ($itemId) {
            // Use the actual item ID if provided, otherwise fall back to index + 1
            $id = $itemId ?? (string) ($index + 1);

            $data = [
                '_id' => $id,
                'type' => $product['type'] instanceof FhrProductType
                    ? $product['type']->value
                    : $product['type'],
                'flight' => $product['flight'] ?? null,
                'vehicle' => $product['vehicle'] ?? null,
            ];

            // Add additional_passengers for lounge orders
            if (isset($product['additional_passengers'])) {
                $data['additional_passengers'] = $product['additional_passengers'];
            }

            return $data;
        })->all();

        $payload = [
            'customer' => $customer->toArray(),
            'products' => $productData,
        ];

        Log::channel(config('fhr.log_channel'))->info('Submitting FHR cart order', [
            'cart_id' => $cartId,
            'item_id' => $itemId,
            'payload' => $payload,
        ]);

        $response = $this->client->post("cart/{$cartId}/order/submit", $payload);

        return FhrOrderResponseData::from([
            'success' => $response['success'] ?? false,
            'order' => $response['order'] ?? null,
            'message' => $response['message'] ?? null,
            'error' => $response['error'] ?? null,
        ]);
    }

    /**
     * Confirm an FHR order after external payment has been processed.
     *
     * Required when we take payment ourselves (e.g. on our own Stripe in
     * `direct` booking mode). FHR expects a confirm call after `submitOrder`
     * to finalise the reservation; without it the order stays in pending
     * state and may eventually expire.
     */
    public function confirmOrder(string $cartId): FhrOrderResponseData
    {
        Log::channel(config('fhr.log_channel'))->info('Confirming FHR cart order', ['cart_id' => $cartId]);

        $response = $this->client->post("cart/{$cartId}/order/confirm", []);

        return FhrOrderResponseData::from([
            'success' => $response['success'] ?? false,
            'order' => $response['order'] ?? $cartId,
            'message' => $response['message'] ?? null,
            'error' => $response['error'] ?? null,
        ]);
    }

    /**
     * Convenience method to submit a parking order.
     */
    public function submitParkingOrder(
        string $cartId,
        FhrCustomerData $customer,
        FhrVehicleData $vehicle,
        ?FhrFlightData $flight = null,
        ?string $itemId = null,
    ): FhrOrderResponseData {
        return $this->submitOrder($cartId, $customer, [
            [
                'type' => FhrProductType::Parking,
                'vehicle' => $vehicle->toArray(),
                'flight' => $flight?->toArray(),
            ],
        ], $itemId);
    }

    /**
     * Convenience method to submit a lounge order.
     *
     * @param  array<int, FhrPassengerData>  $additionalPassengers  Additional passengers beyond the lead customer
     */
    public function submitLoungeOrder(
        string $cartId,
        FhrCustomerData $customer,
        ?FhrFlightData $flight = null,
        ?string $itemId = null,
        array $additionalPassengers = [],
    ): FhrOrderResponseData {
        $productData = [
            'type' => FhrProductType::Lounge,
            'flight' => $flight?->toArray(),
        ];

        // Add additional_passengers if provided
        if (! empty($additionalPassengers)) {
            $productData['additional_passengers'] = array_map(
                fn (FhrPassengerData $p) => $p->toArray(),
                $additionalPassengers
            );
        }

        return $this->submitOrder($cartId, $customer, [$productData], $itemId);
    }

    /**
     * Get Stripe Checkout URL from FHR for a cart.
     *
     * This calls FHR's payment endpoint which returns a Stripe Checkout URL
     * that the customer should be redirected to for payment.
     */
    public function getCheckoutUrl(string $cartId): FhrCheckoutData
    {
        $isSandbox = $this->client->isSandbox();

        // Use sandbox config when test mode is enabled
        $paymentUrl = $isSandbox
            ? (config('fhr.sandbox_payment_url') ?: config('fhr.payment_url'))
            : config('fhr.payment_url');

        $token = $isSandbox
            ? (config('fhr.sandbox_token') ?: config('fhr.token'))
            : config('fhr.token');

        $tenant = $isSandbox
            ? (config('fhr.sandbox_tenant') ?: config('fhr.tenant'))
            : config('fhr.tenant');
        $timeout = config('fhr.timeout', 30);

        if (empty($tenant)) {
            Log::channel(config('fhr.log_channel'))->error('FHR tenant not configured');

            return FhrCheckoutData::failed('FHR tenant not configured');
        }

        $url = "{$paymentUrl}/checkout/{$tenant}/{$cartId}";

        Log::channel(config('fhr.log_channel'))->info('Getting FHR checkout URL', [
            'cart_id' => $cartId,
            'url' => $url,
            'sandbox' => $isSandbox,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'text/plain',
            ])
                ->timeout($timeout)
                ->get($url);

            if (! $response->successful()) {
                Log::channel(config('fhr.log_channel'))->error('FHR checkout URL request failed', [
                    'cart_id' => $cartId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return FhrCheckoutData::failed("FHR checkout request failed with status {$response->status()}");
            }

            $checkoutUrl = trim($response->body());

            if (empty($checkoutUrl) || ! str_starts_with($checkoutUrl, 'http')) {
                Log::channel(config('fhr.log_channel'))->error('FHR checkout URL invalid', [
                    'cart_id' => $cartId,
                    'response' => $checkoutUrl,
                ]);

                return FhrCheckoutData::failed('Invalid checkout URL received from FHR');
            }

            Log::channel(config('fhr.log_channel'))->info('FHR checkout URL retrieved', [
                'cart_id' => $cartId,
                'checkout_url' => $checkoutUrl,
            ]);

            return FhrCheckoutData::success($checkoutUrl);
        } catch (\Exception $e) {
            Log::channel(config('fhr.log_channel'))->error('FHR checkout URL exception', [
                'cart_id' => $cartId,
                'error' => $e->getMessage(),
            ]);

            return FhrCheckoutData::failed($e->getMessage());
        }
    }
}
