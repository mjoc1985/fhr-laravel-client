<?php

namespace Mjoc1985\Fhr\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class FhrException extends Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?Response $response = null,
        public readonly ?array $context = null,
    ) {
        parent::__construct($message, $code);
    }

    public static function fromResponse(Response $response, array $context = []): self
    {
        $body = $response->json() ?? [];
        $message = self::extractErrorMessage($body, 'Unknown FHR API error');

        return new self(
            message: $message,
            code: $response->status(),
            response: $response,
            context: $context,
        );
    }

    /**
     * Extract error message from response body, handling various API response formats.
     */
    protected static function extractErrorMessage(array $body, string $default): string
    {
        if (isset($body['error'])) {
            return $body['error'];
        }

        if (isset($body['message'])) {
            return $body['message'];
        }

        if (isset($body['errors']) && is_array($body['errors'])) {
            $messages = [];
            foreach ($body['errors'] as $field => $errors) {
                $errorList = is_array($errors) ? implode(', ', $errors) : $errors;
                $messages[] = is_string($field) ? "{$field}: {$errorList}" : $errorList;
            }

            return implode('; ', $messages) ?: $default;
        }

        return $default;
    }

    public function getResponseBody(): ?array
    {
        return $this->response?->json();
    }
}
