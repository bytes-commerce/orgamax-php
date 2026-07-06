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
 * Immutable configuration for {@see OrgaMaxClient}.
 *
 * Pass parameters once, then build the client via {@see OrgaMaxClient::fromConfig()}
 * or {@see OrgaMaxClientBuilder::toConfig()}. Withers return a new instance so the
 * DTO is safe to share across threads / long-lived services.
 *
 * The constructor uses nullable types for the optional PSR-18 / PSR-17 / cache /
 * token-provider fields. They are resolved to sensible defaults inside
 * {@see OrgaMaxClient::fromConfig()} — see `phpstan.neon` for the scoped
 * ignoreErrors entry that acknowledges these nullable params are part of the
 * public factory contract.
 */
final readonly class OrgaMaxConfig
{
    public const string DEFAULT_BASE_URL = 'https://api.orgamax.de/openapi';

    public function __construct(
        public string $apiKey,
        public string $apiSecret,
        public string $ownershipId,
        public string $baseUrl,
        public ?ClientInterface $httpClient,
        public ?RequestFactoryInterface $requestFactory,
        public ?StreamFactoryInterface $streamFactory,
        public ?CacheInterface $tokenCache,
        public ?OrgaMaxTokenProvider $tokenProvider,
    ) {
        Assert::stringNotEmpty($apiKey, 'API key must not be empty.');
        Assert::stringNotEmpty($apiSecret, 'API secret must not be empty.');
        Assert::stringNotEmpty($ownershipId, 'Ownership id must not be empty.');
        Assert::stringNotEmpty($baseUrl, 'Base URL must not be empty.');
    }

    /**
     * Named constructor for the common case — production defaults, PSR-18 /
     * PSR-17 auto-discovery, in-memory token cache, cached token provider.
     */
    public static function default(
        string $apiKey,
        string $apiSecret,
        string $ownershipId,
    ): self {
        return new self(
            apiKey: $apiKey,
            apiSecret: $apiSecret,
            ownershipId: $ownershipId,
            baseUrl: self::DEFAULT_BASE_URL,
            httpClient: null,
            requestFactory: null,
            streamFactory: null,
            tokenCache: null,
            tokenProvider: null,
        );
    }

    public function withBaseUrl(string $baseUrl): self
    {
        return new self(
            apiKey: $this->apiKey,
            apiSecret: $this->apiSecret,
            ownershipId: $this->ownershipId,
            baseUrl: $baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            tokenCache: $this->tokenCache,
            tokenProvider: $this->tokenProvider,
        );
    }

    public function withHttpClient(ClientInterface $httpClient): self
    {
        return new self(
            apiKey: $this->apiKey,
            apiSecret: $this->apiSecret,
            ownershipId: $this->ownershipId,
            baseUrl: $this->baseUrl,
            httpClient: $httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            tokenCache: $this->tokenCache,
            tokenProvider: $this->tokenProvider,
        );
    }

    public function withRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        return new self(
            apiKey: $this->apiKey,
            apiSecret: $this->apiSecret,
            ownershipId: $this->ownershipId,
            baseUrl: $this->baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $requestFactory,
            streamFactory: $this->streamFactory,
            tokenCache: $this->tokenCache,
            tokenProvider: $this->tokenProvider,
        );
    }

    public function withStreamFactory(StreamFactoryInterface $streamFactory): self
    {
        return new self(
            apiKey: $this->apiKey,
            apiSecret: $this->apiSecret,
            ownershipId: $this->ownershipId,
            baseUrl: $this->baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $streamFactory,
            tokenCache: $this->tokenCache,
            tokenProvider: $this->tokenProvider,
        );
    }

    public function withTokenCache(CacheInterface $tokenCache): self
    {
        return new self(
            apiKey: $this->apiKey,
            apiSecret: $this->apiSecret,
            ownershipId: $this->ownershipId,
            baseUrl: $this->baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            tokenCache: $tokenCache,
            tokenProvider: $this->tokenProvider,
        );
    }

    /**
     * Shortcut for {@see \BytesCommerce\Orgamax\Auth\StaticTokenProvider}.
     * Wires the supplied JWT directly into the auth pipeline — handy for
     * CI, tests, or scenarios where the token is minted out of band.
     */
    public function withStaticToken(string $bearerToken): self
    {
        return $this->withTokenProvider(new StaticTokenProvider($bearerToken));
    }

    public function withTokenProvider(OrgaMaxTokenProvider $tokenProvider): self
    {
        return new self(
            apiKey: $this->apiKey,
            apiSecret: $this->apiSecret,
            ownershipId: $this->ownershipId,
            baseUrl: $this->baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            tokenCache: $this->tokenCache,
            tokenProvider: $tokenProvider,
        );
    }
}
