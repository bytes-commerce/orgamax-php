<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Auth;

interface OrgaMaxTokenProvider
{
    public function bearerToken(): string;

    public function invalidate(): void;
}
