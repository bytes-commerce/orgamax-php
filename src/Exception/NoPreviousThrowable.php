<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Exception;

use RuntimeException;

/**
 * Sentinel "no previous" Throwable used by exception factories that don't have
 * an underlying cause to chain.
 *
 * This is private to the package — never throw this yourself. We need a
 * non-null `Throwable` to satisfy the typed exception-chain contract of PHP's
 * `Exception::__construct(string, int, ?Throwable)` while obeying strict
 * PHPStan rules that forbid nullable parameter types.
 */
final class NoPreviousThrowable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No previous throwable.');
    }
}
