<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Fixtures\TestDouble;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Replacement for the previous anonymous RuntimeException — used by
 * OrgaMaxAuthenticatorTest::testExchangeOwnershipIdWrapsTransportFailures
 * to simulate a PSR-18 transport failure.
 */
final class FakeClientException extends Exception implements ClientExceptionInterface
{
}
