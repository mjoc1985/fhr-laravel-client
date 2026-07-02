<?php

namespace Mjoc1985\Fhr\Contracts;

use Mjoc1985\Fhr\Logging\NullApiLogger;

/**
 * Records outbound API request/response metadata.
 *
 * The consuming application provides an implementation (for example, one that
 * persists to a database). The package ships {@see NullApiLogger}
 * as a no-op default so the client works with no wiring.
 */
interface ApiLogger
{
    /**
     * @param  array<string, mixed>|null  $requestBody
     * @param  array<string, mixed>|null  $responseBody
     * @param  array<string, mixed>|null  $requestHeaders
     */
    public function log(
        string $provider,
        string $method,
        string $endpoint,
        string $url,
        ?array $requestBody = null,
        ?array $responseBody = null,
        ?array $requestHeaders = null,
        ?int $statusCode = null,
        ?float $durationMs = null,
        bool $isSuccess = true,
        ?string $errorMessage = null,
        bool $isSandbox = false,
    ): void;
}
