<?php

namespace Mjoc1985\Fhr\Services;

use Mjoc1985\Fhr\Exceptions\FhrException;
use Mjoc1985\Fhr\FhrClient;

/**
 * Completed-order lookups.
 */
class FhrOrderService
{
    public function __construct(
        private readonly FhrClient $client,
    ) {}

    public static function make(bool $sandbox = false): self
    {
        return new self(FhrClient::make($sandbox));
    }

    /**
     * Fetch order status and details by GUID.
     *
     * `GET /order/details/{guid}` (public). Only returns orders from roughly
     * the last 15 minutes; older or unknown GUIDs yield a 404 (surfaced as an
     * {@see FhrException}).
     *
     * The response shape is large and varies by product type, so it is
     * returned as a decoded array rather than a typed DTO.
     *
     * @return array<string, mixed>
     */
    public function getOrderDetails(string $guid): array
    {
        return $this->client->get("order/details/{$guid}");
    }
}
