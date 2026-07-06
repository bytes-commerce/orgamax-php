<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Auth;

use BytesCommerce\Orgamax\Auth\CachedTokenProvider;
use BytesCommerce\Orgamax\Auth\InMemoryTokenCache;
use BytesCommerce\Orgamax\Auth\OrgaMaxAuthenticator;
use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CachedTokenProvider::class)]
final class CachedTokenProviderTest extends TestCase
{
    public function testFetchesTokenFromAuthenticatorOnFirstCall(): void
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
            'token' => 'first-jwt',
            'expires_in' => 86400,
        ], \JSON_THROW_ON_ERROR)));

        $provider = CachedTokenProvider::withDefaultTtl(
            authenticator: $authenticator,
            ownershipId: 'ownership-1',
            cache: new InMemoryTokenCache(),
        );

        self::assertSame('first-jwt', $provider->bearerToken());
        self::assertCount(1, $client->sentRequests);
    }

    public function testReturnsCachedTokenOnSubsequentCalls(): void
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

        // Only one enqueued response — second call MUST hit cache, not network.
        $client->enqueue(new FakeResponseWithBody(200, json_encode([
            'token' => 'cached-jwt',
            'expires_in' => 86400,
        ], \JSON_THROW_ON_ERROR)));

        $provider = CachedTokenProvider::withDefaultTtl(
            authenticator: $authenticator,
            ownershipId: 'ownership-2',
            cache: new InMemoryTokenCache(),
        );

        $first = $provider->bearerToken();
        $second = $provider->bearerToken();
        $third = $provider->bearerToken();

        self::assertSame('cached-jwt', $first);
        self::assertSame($first, $second);
        self::assertSame($first, $third);
        self::assertCount(1, $client->sentRequests, 'Authenticator should only be called once');
    }

    public function testInvalidateForcesReAuthentication(): void
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
            'token' => 'first',
            'expires_in' => 86400,
        ], \JSON_THROW_ON_ERROR)));
        $client->enqueue(new FakeResponseWithBody(200, json_encode([
            'token' => 'second',
            'expires_in' => 86400,
        ], \JSON_THROW_ON_ERROR)));

        $provider = CachedTokenProvider::withDefaultTtl(
            authenticator: $authenticator,
            ownershipId: 'ownership-3',
            cache: new InMemoryTokenCache(),
        );

        self::assertSame('first', $provider->bearerToken());
        $provider->invalidate();
        self::assertSame('second', $provider->bearerToken());
        self::assertCount(2, $client->sentRequests);
    }

    public function testStaticProviderReturnsStaticToken(): void
    {
        $provider = new StaticTokenProvider('static-jwt');
        self::assertSame('static-jwt', $provider->bearerToken());
        $provider->update('rotated-jwt');
        self::assertSame('rotated-jwt', $provider->bearerToken());
        $provider->invalidate(); // no-op for static
        self::assertSame('rotated-jwt', $provider->bearerToken());
    }
}
