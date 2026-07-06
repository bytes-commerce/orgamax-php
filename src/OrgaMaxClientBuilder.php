<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax;

use BytesCommerce\Orgamax\Auth\OrgaMaxTokenProvider;
use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

/**
 * Fluent builder for {@see OrgaMaxClient}.
 *
 * Pass auth parameters once via `withApiKey()` / `withApiSecret()` /
 * `withOwnershipId()`, optionally override transport / cache / token via the
 * other `with*()` methods, then call {@see self::build()} (or
 * {@see self::toConfig()} if you want to share an immutable config).
 *
 *     $client = OrgaMaxClientBuilder::create()
 *         ->withApiKey(getenv('ORGAMAX_API_KEY'))
 *         ->withApiSecret(getenv('ORGAMAX_API_SECRET'))
 *         ->withOwnershipId(getenv('ORGAMAX_OWNERSHIP_ID'))
 *         ->build();
 *
 * The builder is **not** immutable — each `with*()` mutates and returns
 * `$this`. That is intentional: builders are throwaway and never shared.
 * Use {@see OrgaMaxConfig} when you need to pass config around.
 */
final class OrgaMaxClientBuilder
{
    private string $apiKey = '';

    private string $apiSecret = '';

    private string $ownershipId = '';

    private string $baseUrl = OrgaMaxConfig::DEFAULT_BASE_URL;

    private ?ClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private ?CacheInterface $tokenCache = null;

    private ?OrgaMaxTokenProvider $tokenProvider = null;

    public static function create(): self
    {
        return new self();
    }

    public function withApiKey(string $apiKey): self
    {
        Assert::stringNotEmpty($apiKey, 'API key must not be empty.');

        $this->apiKey = $apiKey;

        return $this;
    }

    public function withApiSecret(string $apiSecret): self
    {
        Assert::stringNotEmpty($apiSecret, 'API secret must not be empty.');

        $this->apiSecret = $apiSecret;

        return $this;
    }

    public function withOwnershipId(string $ownershipId): self
    {
        Assert::stringNotEmpty($ownershipId, 'Ownership id must not be empty.');

        $this->ownershipId = $ownershipId;

        return $this;
    }

    public function withBaseUrl(string $baseUrl): self
    {
        Assert::stringNotEmpty($baseUrl, 'Base URL must not be empty.');

        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function withHttpClient(ClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function withRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        $this->requestFactory = $requestFactory;

        return $this;
    }

    public function withStreamFactory(StreamFactoryInterface $streamFactory): self
    {
        $this->streamFactory = $streamFactory;

        return $this;
    }

    /**
     * Inject a custom PSR-16 cache. By default the client uses
     * {@see \BytesCommerce\Orgamax\Auth\InMemoryTokenCache}.
     */
    public function withTokenCache(CacheInterface $tokenCache): self
    {
        $this->tokenCache = $tokenCache;

        $this->tokenProvider = null;

        return $this;
    }

    /**
     * Inject a fully custom {@see OrgaMaxTokenProvider}. Wins over
     * `withTokenCache()` — useful for static tokens, secrets-manager backed
     * providers, etc.
     */
    public function withTokenProvider(OrgaMaxTokenProvider $tokenProvider): self
    {
        $this->tokenProvider = $tokenProvider;

        return $this;
    }

    /**
     * Shortcut for `withTokenProvider(new StaticTokenProvider($bearerToken))`.
     */
    public function withStaticToken(string $bearerToken): self
    {
        return $this->withTokenProvider(new StaticTokenProvider($bearerToken));
    }

    /**
     * Freeze the builder state into an immutable {@see OrgaMaxConfig}.
     */
    public function toConfig(): OrgaMaxConfig
    {
        $this->assertRequired();

        return new OrgaMaxConfig(
            apiKey: $this->apiKey,
            apiSecret: $this->apiSecret,
            ownershipId: $this->ownershipId,
            baseUrl: $this->baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            tokenCache: $this->tokenCache,
            tokenProvider: $this->tokenProvider,
        );
    }

    /**
     * Build the configured {@see OrgaMaxClient}. All required fields must be set.
     */
    public function build(): OrgaMaxClient
    {
        return OrgaMaxClient::fromConfig($this->toConfig());
    }

    private function assertRequired(): void
    {
        Assert::stringNotEmpty($this->apiKey, 'API key must be set on the builder.');
        Assert::stringNotEmpty($this->apiSecret, 'API secret must be set on the builder.');
        Assert::stringNotEmpty($this->ownershipId, 'Ownership id must be set on the builder.');
    }
}
