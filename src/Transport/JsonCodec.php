<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Transport;

use Psr\Http\Message\ResponseInterface;

final class JsonCodec
{
    /**
     * @return array<int|string, mixed>|list<mixed>
     */
    public function decode(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($body, true, 64, \JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param array<int|string, mixed>|list<mixed> $payload
     */
    public function responseStatus(array $payload): int
    {
        if (
            array_key_exists('meta', $payload)
            && is_array($payload['meta'])
            && array_key_exists('status', $payload['meta'])
            && is_int($payload['meta']['status'])
        ) {
            return $payload['meta']['status'];
        }

        return 0;
    }
}
