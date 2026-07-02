<?php

namespace Mjoc1985\Fhr\Exceptions;

use Illuminate\Http\Client\Response;

class FhrAuthenticationException extends FhrException
{
    public static function fromResponse(Response $response, array $context = []): self
    {
        $body = $response->json() ?? [];
        $message = self::extractErrorMessage($body, 'Authentication failed');

        return new self(
            message: $message,
            code: $response->status(),
            response: $response,
            context: $context,
        );
    }
}
