<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Auth;

use Override;
use Webmozart\Assert\Assert;

final class StaticTokenProvider implements OrgaMaxTokenProvider
{
    private string $bearerToken;

    public function __construct(string $bearerToken)
    {
        Assert::stringNotEmpty($bearerToken, 'Bearer token must not be empty.');
        $this->bearerToken = $bearerToken;
    }

    #[Override]
    public function bearerToken(): string
    {
        return $this->bearerToken;
    }

    #[Override]
    public function invalidate(): void
    {
        // No-op: a static token is whatever the user gave us.
    }

    public function update(string $bearerToken): void
    {
        Assert::stringNotEmpty($bearerToken, 'Bearer token must not be empty.');
        $this->bearerToken = $bearerToken;
    }
}
