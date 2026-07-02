<?php

namespace Mjoc1985\Fhr\Exceptions;

use Illuminate\Http\Client\Response;

class FhrValidationException extends FhrException
{
    public static function fromResponse(Response $response, array $context = []): self
    {
        $body = $response->json() ?? [];
        $message = self::extractErrorMessage($body, 'Validation failed');

        return new self(
            message: $message,
            code: $response->status(),
            response: $response,
            context: $context,
        );
    }

    public function getValidationErrors(): array
    {
        $body = $this->getResponseBody() ?? [];

        return $body['errors'] ?? [];
    }
}
