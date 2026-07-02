<?php

namespace Mjoc1985\Fhr\Logging;

use Mjoc1985\Fhr\Contracts\ApiLogger;

/**
 * Default no-op {@see ApiLogger}. Used when the consuming app does not bind
 * its own implementation, so the client works standalone.
 */
class NullApiLogger implements ApiLogger
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
    ): void {
        // Intentionally does nothing.
    }
}
