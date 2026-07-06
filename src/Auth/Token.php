<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Auth;

use DateTimeImmutable;

final readonly class Token
{
    public function __construct(
        public string $bearerToken,
        public DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }
}
