<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Util;

use BytesCommerce\Orgamax\Exception\AuthenticationException;
use BytesCommerce\Orgamax\Exception\RateLimitException;
use BytesCommerce\Orgamax\Exception\ServerException;
use BytesCommerce\Orgamax\Exception\TransportException;
use Closure;
use Throwable;

/**
 * Re-runs a callable on transient failures with exponential backoff.
 *
 * Retries on:
 *  - {@see RateLimitException} (honours `Retry-After` when present)
 *  - {@see TransportException} (network blip)
 *  - {@see ServerException} (5xx)
 *  - {@see AuthenticationException} (after invalidating the bearer token so
 *    the next attempt re-mints a fresh one)
 *
 * Other {@see \BytesCommerce\Orgamax\Exception\OrgaMaxException} subclasses
 * (4xx other than the above) are not retried - they indicate a request
 * problem that re-sending will not fix.
 *
 * Sleep is implemented with `usleep()`; for a production retry loop that
 * survives Swoole / Fibers / amphp, wrap your own scheduler around
 * {@see self::computeDelay()}.
 */
final readonly class RetryHandler
{
    public function __construct(
        private int $maxAttempts,
        private int $baseDelayMs,
        private int $maxDelayMs,
    ) {
    }

    /**
     * Sensible defaults: 3 attempts, 200ms base delay, capped at 5s.
     */
    public static function default(): self
    {
        return new self(maxAttempts: 3, baseDelayMs: 200, maxDelayMs: 5_000);
    }

    /**
     * @template T
     *
     * @param Closure(): T $fn
     *
     * @return T
     */
    public function run(Closure $fn): mixed
    {
        $attempt = 0;
        while (true) {
            try {
                return $fn();
            } catch (RateLimitException $e) {
                $this->throwIfExhausted($e, ++$attempt);
                $delayMs = $e->hasRetryAfter()
                    ? $e->retryAfter() * 1_000
                    : $this->computeDelay($attempt);
            } catch (AuthenticationException|TransportException | ServerException $e) {
                $this->throwIfExhausted($e, ++$attempt);
                $delayMs = $this->computeDelay($attempt);
            }

            usleep($delayMs * 1_000);
        }
    }

    /**
     * Compute the backoff (in ms) for the given attempt number (1-based).
     * Public so callers can wrap their own scheduler.
     */
    public function computeDelay(int $attempt): int
    {
        $delay = $this->baseDelayMs * (2 ** ($attempt - 1));

        return min($delay, $this->maxDelayMs);
    }

    private function throwIfExhausted(Throwable $last, int $attempt): void
    {
        if ($attempt > $this->maxAttempts) {
            throw $last;
        }
    }
}
