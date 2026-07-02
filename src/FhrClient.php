<?php

namespace Mjoc1985\Fhr;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mjoc1985\Fhr\Contracts\ApiLogger;
use Mjoc1985\Fhr\Exceptions\FhrAuthenticationException;
use Mjoc1985\Fhr\Exceptions\FhrException;
use Mjoc1985\Fhr\Exceptions\FhrRateLimitException;
use Mjoc1985\Fhr\Exceptions\FhrValidationException;
use Mjoc1985\Fhr\Logging\NullApiLogger;

class FhrClient
{
    private const RATE_LIMIT_CACHE_KEY = 'fhr:rate_limit:requests';

    private readonly ApiLogger $apiLogger;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
        private readonly string $source,
        private readonly int $timeout = 30,
        private readonly int $retryTimes = 3,
        private readonly int $retrySleepMs = 500,
        private readonly int $rateLimitMaxRequests = 100,
        private readonly int $rateLimitWindowSeconds = 60,
        private readonly bool $isSandbox = false,
        ?ApiLogger $apiLogger = null,
    ) {
        $this->apiLogger = $apiLogger ?? new NullApiLogger;
    }

    /**
     * Build a client from configuration.
     *
     * Sandbox vs production credentials are selected by the caller (the
     * consuming app decides, e.g. from its own "test mode" flag) rather than
     * the package reaching into app state. The API logger is resolved from the
     * container when bound, otherwise a no-op logger is used.
     */
    public static function make(bool $sandbox = false, ?ApiLogger $apiLogger = null): self
    {
        $baseUrl = $sandbox
            ? (config('fhr.sandbox_base_url') ?: config('fhr.base_url'))
            : config('fhr.base_url');

        $token = $sandbox
            ? (config('fhr.sandbox_token') ?: config('fhr.token'))
            : config('fhr.token');

        return new self(
            baseUrl: $baseUrl ?? '',
            token: $token ?? '',
            source: config('fhr.source') ?? '',
            timeout: config('fhr.timeout', 30),
            retryTimes: config('fhr.retry_times', 3),
            retrySleepMs: config('fhr.retry_sleep_ms', 500),
            rateLimitMaxRequests: config('fhr.rate_limit.max_requests', 100),
            rateLimitWindowSeconds: config('fhr.rate_limit.window_seconds', 60),
            isSandbox: $sandbox,
            apiLogger: $apiLogger ?? self::resolveApiLogger(),
        );
    }

    private static function resolveApiLogger(): ApiLogger
    {
        if (function_exists('app') && app()->bound(ApiLogger::class)) {
            return app(ApiLogger::class);
        }

        return new NullApiLogger;
    }

    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, $data);
    }

    public function getSource(): string
    {
        return $this->source;
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $this->checkRateLimit();

        $url = $this->buildUrl($endpoint);
        $startTime = microtime(true);

        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'url' => $url,
        ];

        try {
            $response = $this->makeRequest($method, $url, $data);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logRequest($context, $response, $duration);
            $this->logToApiLogger($method, $endpoint, $url, $data, $response->json() ?? [], $response->status(), $duration, true);
            $this->incrementRateLimit();

            return $response->json() ?? [];
        } catch (FhrException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logError($context, $e);
            $this->logToApiLogger($method, $endpoint, $url, $data, $e->getResponseBody(), $e->getCode(), $duration, false, $e->getMessage());
            throw $e;
        }
    }

    private function makeRequest(string $method, string $url, array $data): Response
    {
        $response = $this->client()
            ->retry($this->retryTimes, $this->retrySleepMs, function (\Throwable $exception, PendingRequest $request) {
                // Only retry on server errors (5xx)
                if ($exception instanceof RequestException) {
                    return $exception->response->serverError();
                }

                return false;
            }, throw: false)
            ->{strtolower($method)}($url, $data);

        $this->handleErrorResponse($response, ['method' => $method, 'url' => $url, 'data' => $data]);

        return $response;
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout);
    }

    private function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');

        return "{$this->baseUrl}/{$endpoint}";
    }

    private function handleErrorResponse(Response $response, array $context): void
    {
        if ($response->successful()) {
            return;
        }

        $exception = match ($response->status()) {
            401, 403 => FhrAuthenticationException::fromResponse($response, $context),
            422 => FhrValidationException::fromResponse($response, $context),
            429 => FhrRateLimitException::fromResponse($response, $context),
            default => FhrException::fromResponse($response, $context),
        };

        throw $exception;
    }

    /**
     * Check if we've exceeded the client-side rate limit.
     *
     * @throws FhrRateLimitException
     */
    private function checkRateLimit(): void
    {
        $requests = Cache::get(self::RATE_LIMIT_CACHE_KEY, 0);

        if ($requests >= $this->rateLimitMaxRequests) {
            Log::channel(config('fhr.log_channel'))->warning('FHR API client-side rate limit exceeded', [
                'requests' => $requests,
                'max' => $this->rateLimitMaxRequests,
            ]);

            throw new FhrRateLimitException(
                message: 'Client-side rate limit exceeded. Too many requests in the current window.',
                code: 429,
            );
        }

        // Warn when approaching the limit (90% threshold)
        $warningThreshold = (int) ($this->rateLimitMaxRequests * 0.9);
        if ($requests >= $warningThreshold) {
            Log::channel(config('fhr.log_channel'))->warning('FHR API rate limit approaching', [
                'requests' => $requests,
                'max' => $this->rateLimitMaxRequests,
                'percentage' => round(($requests / $this->rateLimitMaxRequests) * 100, 1),
            ]);
        }
    }

    /**
     * Increment the rate limit counter atomically.
     */
    private function incrementRateLimit(): void
    {
        $key = self::RATE_LIMIT_CACHE_KEY;

        // Use atomic add - sets to 0 with TTL if key doesn't exist, does nothing if it does
        Cache::add($key, 0, $this->rateLimitWindowSeconds);

        // Then increment (this is atomic)
        Cache::increment($key);
    }

    private function logRequest(array $context, Response $response, float $durationMs): void
    {
        Log::channel(config('fhr.log_channel'))->info('FHR API request', [
            ...$context,
            'status' => $response->status(),
            'duration_ms' => $durationMs,
            'sandbox' => $this->isSandbox,
        ]);
    }

    private function logError(array $context, FhrException $exception): void
    {
        Log::channel(config('fhr.log_channel'))->error('FHR API error', [
            ...$context,
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'response' => $exception->getResponseBody(),
        ]);
    }

    /**
     * Log the API request/response through the injected ApiLogger.
     *
     * The consuming app implements ApiLogger (e.g. persisting to a database);
     * standalone, the NullApiLogger no-ops.
     *
     * @param  array<string, mixed>  $requestBody
     * @param  array<string, mixed>|null  $responseBody
     */
    private function logToApiLogger(
        string $method,
        string $endpoint,
        string $url,
        array $requestBody,
        ?array $responseBody,
        int $statusCode,
        float $durationMs,
        bool $isSuccess,
        ?string $errorMessage = null,
    ): void {
        try {
            $this->apiLogger->log(
                provider: 'fhr',
                method: $method,
                endpoint: $endpoint,
                url: $url,
                requestBody: $requestBody,
                responseBody: $responseBody,
                requestHeaders: [
                    'Authorization' => 'Bearer [REDACTED]',
                    'Accept' => 'application/json',
                ],
                statusCode: $statusCode,
                durationMs: $durationMs,
                isSuccess: $isSuccess,
                errorMessage: $errorMessage,
                isSandbox: $this->isSandbox,
            );
        } catch (\Throwable $e) {
            Log::channel(config('fhr.log_channel'))->warning('Failed to log FHR API request', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
