<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Exception;

use Throwable;

final class RateLimitException extends OrgaMaxException
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

    public function retryAfter(): int
    {
        if (array_key_exists('retry-after', $this->responseHeaders())) {
            $header = $this->responseHeaders()['retry-after'];
            if (is_numeric($header)) {
                return (int) $header;
            }
        }

        return 0;
    }

    public function hasRetryAfter(): bool
    {
        return $this->retryAfter() > 0;
    }
}
