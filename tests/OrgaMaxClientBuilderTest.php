<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests;

use BytesCommerce\Orgamax\Auth\InMemoryTokenCache;
use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\OrgaMaxClientBuilder;
use BytesCommerce\Orgamax\OrgaMaxConfig;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

#[CoversClass(OrgaMaxClientBuilder::class)]
#[CoversClass(OrgaMaxClient::class)]
final class OrgaMaxClientBuilderTest extends TestCase
{
    public function testBuilderReturnsFluentSelfOnEachCall(): void
    {
        $builder = OrgaMaxClientBuilder::create();

        self::assertSame($builder, $builder->withApiKey('k'));
        self::assertSame($builder, $builder->withApiSecret('s'));
        self::assertSame($builder, $builder->withOwnershipId('o'));
        self::assertSame($builder, $builder->withBaseUrl('https://example.com/openapi'));
        self::assertSame($builder, $builder->withHttpClient(new FakeHttpClient()));
        self::assertSame($builder, $builder->withRequestFactory(new Psr17Factory()));
        self::assertSame($builder, $builder->withStreamFactory(new Psr17Factory()));
        self::assertSame($builder, $builder->withTokenCache(new InMemoryTokenCache()));
        self::assertSame($builder, $builder->withStaticToken('jwt'));
    }

    public function testBuildProducesFullyConfiguredClient(): void
    {
        $client = OrgaMaxClientBuilder::create()
            ->withApiKey('my-key')
            ->withApiSecret('my-secret')
            ->withOwnershipId('ownership-42')
            ->withHttpClient(new FakeHttpClient())
            ->withRequestFactory(new Psr17Factory())
            ->withStreamFactory(new Psr17Factory())
            ->build();

        self::assertInstanceOf(OrgaMaxClient::class, $client);
        self::assertNotNull($client->articles());
    }

    public function testBuildWithStaticToken(): void
    {
        $client = OrgaMaxClientBuilder::create()
            ->withApiKey('k')
            ->withApiSecret('s')
            ->withOwnershipId('o')
            ->withStaticToken('pre-minted-jwt')
            ->withHttpClient(new FakeHttpClient())
            ->withRequestFactory(new Psr17Factory())
            ->withStreamFactory(new Psr17Factory())
            ->build();

        self::assertInstanceOf(OrgaMaxClient::class, $client);
    }

    public function testBuildWithoutRequiredFieldThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrgaMaxClientBuilder::create()
            ->withApiKey('k')
            ->withApiSecret('s')
            ->withHttpClient(new FakeHttpClient())
            ->withRequestFactory(new Psr17Factory())
            ->withStreamFactory(new Psr17Factory())
            ->build();
    }

    public function testRejectsEmptyApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrgaMaxClientBuilder::create()->withApiKey('');
    }

    public function testRejectsEmptyOwnershipId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrgaMaxClientBuilder::create()->withOwnershipId('');
    }

    public function testToConfigFreezesImmutableSnapshot(): void
    {
        $builder = OrgaMaxClientBuilder::create()
            ->withApiKey('k')
            ->withApiSecret('s')
            ->withOwnershipId('o')
            ->withBaseUrl('https://staging.example.com/openapi');

        $config = $builder->toConfig();

        self::assertInstanceOf(OrgaMaxConfig::class, $config);
        self::assertSame('k', $config->apiKey);
        self::assertSame('s', $config->apiSecret);
        self::assertSame('o', $config->ownershipId);
        self::assertSame('https://staging.example.com/openapi', $config->baseUrl);

        // Builder is still mutable — mutating it must NOT affect the frozen config.
        $builder->withApiKey('different-key');
        self::assertSame('k', $config->apiKey, 'config must be unaffected by later builder mutations');
    }

    public function testClientBuilderShortcutMatchesStaticFactory(): void
    {
        self::assertInstanceOf(OrgaMaxClientBuilder::class, OrgaMaxClient::builder());
    }

    public function testBuildWithCustomHttpClientAndCache(): void
    {
        $client = OrgaMaxClientBuilder::create()
            ->withApiKey('k')
            ->withApiSecret('s')
            ->withOwnershipId('o')
            ->withHttpClient(new FakeHttpClient())
            ->withTokenCache(new InMemoryTokenCache())
            ->build();

        self::assertInstanceOf(OrgaMaxClient::class, $client);
    }
}
