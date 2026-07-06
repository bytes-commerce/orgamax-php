<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Util;

use BytesCommerce\Orgamax\Exception\RateLimitException;
use BytesCommerce\Orgamax\Exception\ServerException;
use BytesCommerce\Orgamax\Exception\TransportException;
use BytesCommerce\Orgamax\Exception\ValidationException;
use BytesCommerce\Orgamax\Util\RetryHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(RetryHandler::class)]
final class RetryHandlerTest extends TestCase
{
    public function testReturnsImmediatelyOnSuccess(): void
    {
        $handler = RetryHandler::default();

        $result = $handler->run(static fn (): string => 'ok');

        self::assertSame('ok', $result);
    }

    public function testRetriesRateLimitExceptionThenSucceeds(): void
    {
        $handler = new RetryHandler(maxAttempts: 3, baseDelayMs: 1, maxDelayMs: 5);

        $calls = 0;
        $result = $handler->run(static function () use (&$calls): string {
            ++$calls;
            if ($calls < 3) {
                throw new RateLimitException('rate limited', 429, [], [], new RuntimeException());
            }

            return 'ok';
        });

        self::assertSame('ok', $result);
        self::assertSame(3, $calls);
    }

    public function testRetriesTransportException(): void
    {
        $handler = new RetryHandler(maxAttempts: 2, baseDelayMs: 1, maxDelayMs: 5);

        $calls = 0;
        $result = $handler->run(static function () use (&$calls): string {
            ++$calls;
            if ($calls === 1) {
                throw new TransportException('boom', 0, [], [], new RuntimeException());
            }

            return 'ok';
        });

        self::assertSame('ok', $result);
        self::assertSame(2, $calls);
    }

    public function testRetriesServerException(): void
    {
        $handler = new RetryHandler(maxAttempts: 2, baseDelayMs: 1, maxDelayMs: 5);

        $calls = 0;
        $result = $handler->run(static function () use (&$calls): string {
            ++$calls;
            if ($calls === 1) {
                throw new ServerException('oops', 500, [], [], new RuntimeException());
            }

            return 'ok';
        });

        self::assertSame('ok', $result);
    }

    public function testDoesNotRetryValidationException(): void
    {
        $handler = new RetryHandler(maxAttempts: 3, baseDelayMs: 1, maxDelayMs: 5);

        $calls = 0;
        try {
            $handler->run(static function () use (&$calls): string {
                ++$calls;
                throw new ValidationException('bad payload', 422, [], [], new RuntimeException());
            });
        } catch (ValidationException) {
            // expected
        }

        self::assertSame(1, $calls, 'must not retry 4xx errors that are not rate-limit or auth');
    }

    public function testThrowsAfterMaxAttempts(): void
    {
        $handler = new RetryHandler(maxAttempts: 2, baseDelayMs: 1, maxDelayMs: 5);

        $calls = 0;
        try {
            $handler->run(static function () use (&$calls): string {
                ++$calls;
                throw new TransportException('always fails', 0, [], [], new RuntimeException());
            });
        } catch (TransportException) {
            // expected
        }

        self::assertSame(3, $calls, 'first attempt + 2 retries = 3 calls');
    }

    public function testComputeDelayIsExponentialAndCapped(): void
    {
        $handler = new RetryHandler(maxAttempts: 10, baseDelayMs: 100, maxDelayMs: 500);

        self::assertSame(100, $handler->computeDelay(1));
        self::assertSame(200, $handler->computeDelay(2));
        self::assertSame(400, $handler->computeDelay(3));
        self::assertSame(500, $handler->computeDelay(4), 'capped at maxDelayMs');
        self::assertSame(500, $handler->computeDelay(10), 'capped at maxDelayMs');
    }
}
