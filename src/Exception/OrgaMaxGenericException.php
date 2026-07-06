<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Exception;

use Throwable;

/**
 * Concrete fallback for API error responses that don't map to a more specific
 * exception type (e.g. 4xx codes outside 401/404/422/429).
 */
final class OrgaMaxGenericException extends OrgaMaxException
{
    /**
     * @param array<string, mixed>  $responseBody
     * @param array<string, string> $responseHeaders
     */
    public function __construct(
        string $message,
        int $httpStatusCode,
        array $responseBody,
        array $responseHeaders,
        Throwable $previous,
    ) {
        parent::__construct($message, $httpStatusCode, $responseBody, $responseHeaders, $previous);
    }
}
