<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Model\Common;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Wraps a binary HTTP response (PDF, image, ...) returned by document endpoints.
 *
 * Some OrgaMax endpoints stream a binary payload back instead of a JSON envelope.
 * The caller can decide whether to stream it to disk, hand it to a browser, etc.
 */
final readonly class BinaryResponse
{
    public function __construct(
        public int $statusCode,
        public string $contentType,
        public StreamInterface $body,
    ) {
    }

    public static function fromPsrResponse(ResponseInterface $response): self
    {
        return new self(
            statusCode: $response->getStatusCode(),
            contentType: $response->getHeaderLine('Content-Type'),
            body: $response->getBody(),
        );
    }

    public function toString(): string
    {
        return (string) $this->body;
    }
}
