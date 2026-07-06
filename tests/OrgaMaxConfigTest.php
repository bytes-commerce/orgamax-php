<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests;

use BytesCommerce\Orgamax\Auth\CachedTokenProvider;
use BytesCommerce\Orgamax\Auth\InMemoryTokenCache;
use BytesCommerce\Orgamax\Auth\OrgaMaxAuthenticator;
use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\OrgaMaxConfig;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\InvalidArgumentException;

#[CoversClass(OrgaMaxConfig::class)]
final class OrgaMaxConfigTest extends TestCase
{
    public function testDefaultUsesProductionBaseUrl(): void
    {
        $config = OrgaMaxConfig::default('k', 's', 'o');

        self::assertSame('k', $config->apiKey);
        self::assertSame('s', $config->apiSecret);
        self::assertSame('o', $config->ownershipId);
        self::assertSame(OrgaMaxConfig::DEFAULT_BASE_URL, $config->baseUrl);
        self::assertNull($config->httpClient);
        self::assertNull($config->requestFactory);
        self::assertNull($config->streamFactory);
        self::assertNull($config->tokenCache);
        self::assertNull($config->tokenProvider);
    }

    public function testWithersReturnNewInstanceAndPreserveOtherFields(): void
    {
        $original = OrgaMaxConfig::default('k', 's', 'o');
        $httpClient = new FakeHttpClient();
        $psr17 = new Psr17Factory();

        $modified = $original
            ->withBaseUrl('https://staging.example.com/openapi')
            ->withHttpClient($httpClient)
            ->withRequestFactory($psr17)
            ->withStreamFactory($psr17)
            ->withTokenCache(new InMemoryTokenCache());

        self::assertNotSame($original, $modified, 'withers must return a new instance');
        self::assertSame(OrgaMaxConfig::DEFAULT_BASE_URL, $original->baseUrl, 'original must stay untouched');
        self::assertNull($original->httpClient);
        self::assertSame('https://staging.example.com/openapi', $modified->baseUrl);
        self::assertSame($httpClient, $modified->httpClient);
        self::assertSame($psr17, $modified->requestFactory);
        self::assertSame($psr17, $modified->streamFactory);
        self::assertInstanceOf(CacheInterface::class, $modified->tokenCache);
        self::assertNull($modified->tokenProvider);
    }

    public function testWithStaticTokenWiresStaticTokenProvider(): void
    {
        $config = OrgaMaxConfig::default('k', 's', 'o')->withStaticToken('my-jwt');

        self::assertInstanceOf(StaticTokenProvider::class, $config->tokenProvider);
        self::assertSame('my-jwt', $config->tokenProvider->bearerToken());
    }

    public function testWithTokenProviderAcceptsAnyImplementation(): void
    {
        $authenticator = new OrgaMaxAuthenticator(
            apiKey: 'k',
            apiSecret: 's',
            httpClient: new FakeHttpClient(),
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
            baseUrl: 'https://api.orgamax.de/openapi',
        );
        $provider = CachedTokenProvider::withDefaultTtl(
            authenticator: $authenticator,
            ownershipId: 'o',
            cache: new InMemoryTokenCache(),
        );

        $config = OrgaMaxConfig::default('k', 's', 'o')->withTokenProvider($provider);

        self::assertSame($provider, $config->tokenProvider);
    }

    public function testRejectsEmptyApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrgaMaxConfig::default('', 's', 'o');
    }

    public function testRejectsEmptyApiSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrgaMaxConfig::default('k', '', 'o');
    }

    public function testRejectsEmptyOwnershipId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrgaMaxConfig::default('k', 's', '');
    }

    public function testHttpClientIsTypedAsPsr18(): void
    {
        $config = OrgaMaxConfig::default('k', 's', 'o')->withHttpClient(new FakeHttpClient());

        self::assertInstanceOf(ClientInterface::class, $config->httpClient);
        self::assertInstanceOf(RequestFactoryInterface::class, $config->requestFactory ?? new Psr17Factory());
        self::assertInstanceOf(StreamFactoryInterface::class, $config->streamFactory ?? new Psr17Factory());
    }
}
