<?php

namespace App\Services\OneAuto;

use RuntimeException;

/**
 * Carries enough for server-side logging (endpoint, HTTP status, the
 * provider's own error message) without ever being rendered directly to a
 * customer — callers catch this and show a generic friendly message.
 */
class OneAutoApiException extends RuntimeException
{
    public function __construct(
        public readonly string $endpoint,
        public readonly ?int $httpStatus,
        string $message,
    ) {
        parent::__construct($message);
    }
}
