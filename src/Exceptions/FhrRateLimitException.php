<?php

namespace Mjoc1985\Fhr\Exceptions;

use Illuminate\Http\Client\Response;

class FhrRateLimitException extends FhrException
{
    public static function fromResponse(Response $response, array $context = []): self
    {
        $body = $response->json() ?? [];
        $message = self::extractErrorMessage($body, 'Rate limit exceeded');

        return new self(
            message: $message,
            code: $response->status(),
            response: $response,
            context: $context,
        );
    }

    public function getRetryAfter(): ?int
    {
        return $this->response?->header('Retry-After')
            ? (int) $this->response->header('Retry-After')
            : null;
    }
}
