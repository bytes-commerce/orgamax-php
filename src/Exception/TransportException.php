<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Exception;

use Throwable;

final class TransportException extends OrgaMaxException
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
