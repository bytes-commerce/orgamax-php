<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Model\Common;

/**
 * Result of `POST /auth/token`.
 *
 * `bearerToken` is the JWT to send as `Authorization: Bearer …` on subsequent calls.
 * `expiresAt` is an absolute UNIX timestamp derived from `expires_in`.
 */
final readonly class TokenResponse
{
    public function __construct(
        public string $bearerToken,
        public int $expiresIn,
        public int $expiresAt,
    ) {
    }

    /**
     * @param array<int|string, mixed>|list<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $rawToken = $payload['token'] ?? '';
        $token = is_string($rawToken) ? $rawToken : '';

        $expiresIn = 0;
        $rawExpiresIn = $payload['expires_in'] ?? null;
        if (is_int($rawExpiresIn)) {
            $expiresIn = $rawExpiresIn;
        } elseif (is_numeric($rawExpiresIn)) {
            $expiresIn = (int) $rawExpiresIn;
        }

        return new self(
            bearerToken: $token,
            expiresIn: $expiresIn,
            expiresAt: time() + $expiresIn,
        );
    }
}
