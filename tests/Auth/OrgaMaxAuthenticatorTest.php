<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Auth;

use BytesCommerce\Orgamax\Auth\OrgaMaxAuthenticator;
use BytesCommerce\Orgamax\Exception\AuthenticationException;
use BytesCommerce\Orgamax\Exception\TransportException;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeClientException;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrgaMaxAuthenticator::class)]
final class OrgaMaxAuthenticatorTest extends TestCase
{
    public function testExchangeOwnershipIdSendsBasicAuthAndReturnsToken(): void
    {
        $client = new FakeHttpClient();
        $psr17 = new Psr17Factory();
        $authenticator = new OrgaMaxAuthenticator(
            apiKey: 'my-key',
            apiSecret: 'my-secret',
            httpClient: $client,
            requestFactory: $psr17,
            streamFactory: $psr17,
            baseUrl: 'https://api.orgamax.de/openapi',
        );

        $client->enqueue(new FakeResponseWithBody(200, json_encode([
            'token' => 'jwt-fake',
            'expires_in' => 86400,
        ], \JSON_THROW_ON_ERROR)));

        $token = $authenticator->exchangeOwnershipId('ownership-42');

        self::assertSame('jwt-fake', $token->bearerToken);

        $sent = $client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/auth/token', $sent->getUri()->getPath());
        self::assertSame(
            'Basic ' . base64_encode('my-key:my-secret'),
            $sent->getHeaderLine('Authorization'),
        );
        self::assertStringContainsString('"ownershipId":"ownership-42"', (string) $sent->getBody());
    }

    public function testExchangeOwnershipIdThrowsOnNon2xx(): void
    {
        $client = new FakeHttpClient();
        $psr17 = new Psr17Factory();
        $authenticator = new OrgaMaxAuthenticator(
            apiKey: 'k',
            apiSecret: 's',
            httpClient: $client,
            requestFactory: $psr17,
            streamFactory: $psr17,
            baseUrl: 'https://api.orgamax.de/openapi',
        );

        $client->enqueue(new FakeResponseWithBody(401, json_encode([
            'error' => 'bad creds',
        ], \JSON_THROW_ON_ERROR)));

        $this->expectException(AuthenticationException::class);
        $authenticator->exchangeOwnershipId('o1');
    }

    public function testExchangeOwnershipIdThrowsWhenResponseMissingToken(): void
    {
        $client = new FakeHttpClient();
        $psr17 = new Psr17Factory();
        $authenticator = new OrgaMaxAuthenticator(
            apiKey: 'k',
            apiSecret: 's',
            httpClient: $client,
            requestFactory: $psr17,
            streamFactory: $psr17,
            baseUrl: 'https://api.orgamax.de/openapi',
        );

        $client->enqueue(new FakeResponseWithBody(200, json_encode([
            'unexpected' => 'shape',
        ], \JSON_THROW_ON_ERROR)));

        $this->expectException(AuthenticationException::class);
        $authenticator->exchangeOwnershipId('o1');
    }

    public function testExchangeOwnershipIdWrapsTransportFailures(): void
    {
        $client = new FakeHttpClient();
        $psr17 = new Psr17Factory();
        $authenticator = new OrgaMaxAuthenticator(
            apiKey: 'k',
            apiSecret: 's',
            httpClient: $client,
            requestFactory: $psr17,
            streamFactory: $psr17,
            baseUrl: 'https://api.orgamax.de/openapi',
        );

        $client->enqueue(new FakeClientException());

        $this->expectException(TransportException::class);
        $authenticator->exchangeOwnershipId('o1');
    }
}
