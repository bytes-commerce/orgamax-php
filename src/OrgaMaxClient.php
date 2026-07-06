<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax;

use BytesCommerce\Orgamax\Auth\CachedTokenProvider;
use BytesCommerce\Orgamax\Auth\InMemoryTokenCache;
use BytesCommerce\Orgamax\Auth\OrgaMaxAuthenticator;
use BytesCommerce\Orgamax\Auth\OrgaMaxTokenProvider;
use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Resource\Article;
use BytesCommerce\Orgamax\Resource\Bookkeeping;
use BytesCommerce\Orgamax\Resource\Customer;
use BytesCommerce\Orgamax\Resource\DeliveryCondition;
use BytesCommerce\Orgamax\Resource\DeliveryNote;
use BytesCommerce\Orgamax\Resource\Expense;
use BytesCommerce\Orgamax\Resource\File;
use BytesCommerce\Orgamax\Resource\Invoice;
use BytesCommerce\Orgamax\Resource\Offer;
use BytesCommerce\Orgamax\Resource\Order;
use BytesCommerce\Orgamax\Resource\PayCondition;
use BytesCommerce\Orgamax\Resource\Setting;
use BytesCommerce\Orgamax\Resource\Supplier;
use BytesCommerce\Orgamax\Resource\Tag;
use BytesCommerce\Orgamax\Resource\Todo;
use BytesCommerce\Orgamax\Resource\User;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use BytesCommerce\Orgamax\Util\Pagination;
use Generator;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

/**
 * Top-level facade: constructs all resources and wires authentication.
 *
 * Three entry points, ordered by verbosity / control:
 *
 *   1. {@see self::builder()}   - fluent builder (recommended for app code)
 *   2. {@see self::create()}    - named-arg factory for simple setups
 *   3. {@see self::fromConfig()} - immutable config DTO; ideal for DI containers
 *
 * The 8-arg public constructor is also kept for callers that already have
 * pre-built transport / auth dependencies and want full control.
 *
 * Resources are exposed via plural accessor methods (`articles()`,
 * `customers()`, ...). These are the only public way to reach a resource -
 * there is no parallel property surface.
 */
final readonly class OrgaMaxClient
{
    /**
     * Default page size used by every resource's `list()` method when the
     * caller does not pass one. The OpenAPI does not publish a hard limit,
     * so 50 is a comfortable middle ground.
     */
    public const int DEFAULT_PAGE_SIZE = 50;

    private OrgaMaxAuthenticator $authenticator;

    private RequestBuilder $requestBuilder;

    private ResponseHandler $responseHandler;

    private JsonCodec $jsonCodec;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        string $ownershipId,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        OrgaMaxTokenProvider $tokenProvider,
        string $baseUrl,
    ) {
        Assert::stringNotEmpty($apiKey, 'API key must not be empty.');
        Assert::stringNotEmpty($apiSecret, 'API secret must not be empty.');
        Assert::stringNotEmpty($ownershipId, 'Ownership id must not be empty.');
        Assert::stringNotEmpty($baseUrl, 'Base URL must not be empty.');

        $this->authenticator = new OrgaMaxAuthenticator(
            apiKey: $apiKey,
            apiSecret: $apiSecret,
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            baseUrl: $baseUrl,
        );

        $this->requestBuilder = new RequestBuilder(
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            tokenProvider: $tokenProvider,
            baseUrl: $baseUrl,
        );
        $this->responseHandler = new ResponseHandler($httpClient);
        $this->jsonCodec = new JsonCodec();
    }

    /**
     * Shortcut into {@see OrgaMaxClientBuilder::create()}.
     *
     *     $client = OrgaMaxClient::builder()
     *         ->withApiKey('...')
     *         ->withApiSecret('...')
     *         ->withOwnershipId('...')
     *         ->build();
     */
    public static function builder(): OrgaMaxClientBuilder
    {
        return OrgaMaxClientBuilder::create();
    }

    /**
     * Build a client from an immutable {@see OrgaMaxConfig}. The single code
     * path used by all other factories.
     *
     * Any `null` field on the config is resolved to a sensible default:
     *  - `httpClient` / `requestFactory` / `streamFactory` -> php-http/discovery
     *  - `tokenCache` -> {@see InMemoryTokenCache}
     *  - `tokenProvider` -> {@see CachedTokenProvider::withDefaultTtl()}
     */
    public static function fromConfig(OrgaMaxConfig $config): self
    {
        $httpClient = $config->httpClient ?? Psr18ClientDiscovery::find();
        $requestFactory = $config->requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = $config->streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();

        $authenticator = new OrgaMaxAuthenticator(
            apiKey: $config->apiKey,
            apiSecret: $config->apiSecret,
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            baseUrl: $config->baseUrl,
        );

        $tokenProvider = $config->tokenProvider ?? CachedTokenProvider::withDefaultTtl(
            authenticator: $authenticator,
            ownershipId: $config->ownershipId,
            cache: $config->tokenCache ?? new InMemoryTokenCache(),
        );

        return new self(
            apiKey: $config->apiKey,
            apiSecret: $config->apiSecret,
            ownershipId: $config->ownershipId,
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            tokenProvider: $tokenProvider,
            baseUrl: $config->baseUrl,
        );
    }

    /**
     * Convenience factory - discovers PSR-18 / PSR-17 implementations and uses
     * an {@see InMemoryTokenCache} for token storage.
     *
     * Typical setup:
     *
     *     $client = OrgaMaxClient::create(
     *         apiKey: '...',
     *         apiSecret: '...',
     *         ownershipId: '12345',
     *     );
     */
    public static function create(
        string $apiKey,
        string $apiSecret,
        string $ownershipId,
        string $baseUrl = OrgaMaxConfig::DEFAULT_BASE_URL,
    ): self {
        return self::fromConfig(
            OrgaMaxConfig::default($apiKey, $apiSecret, $ownershipId)->withBaseUrl($baseUrl),
        );
    }

    /**
     * Convenience factory - discovers PSR-18 / PSR-17 implementations and uses
     * the supplied PSR-16 cache (Redis, filesystem, etc.) for token storage.
     */
    public static function createWithCache(
        string $apiKey,
        string $apiSecret,
        string $ownershipId,
        CacheInterface $tokenCache,
        string $baseUrl = OrgaMaxConfig::DEFAULT_BASE_URL,
    ): self {
        return self::fromConfig(
            OrgaMaxConfig::default($apiKey, $apiSecret, $ownershipId)
                ->withBaseUrl($baseUrl)
                ->withTokenCache($tokenCache),
        );
    }

    // --- plural accessor methods (the only public way to reach a resource) --

    public function articles(): Article
    {
        return new Article($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function bookkeepings(): Bookkeeping
    {
        return new Bookkeeping($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function customers(): Customer
    {
        return new Customer($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function deliveryConditions(): DeliveryCondition
    {
        return new DeliveryCondition($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function deliveryNotes(): DeliveryNote
    {
        return new DeliveryNote($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function expenses(): Expense
    {
        return new Expense($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function files(): File
    {
        return new File($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function invoices(): Invoice
    {
        return new Invoice($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function offers(): Offer
    {
        return new Offer($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function orders(): Order
    {
        return new Order($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function payConditions(): PayCondition
    {
        return new PayCondition($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function settings(): Setting
    {
        return new Setting($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function suppliers(): Supplier
    {
        return new Supplier($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function tags(): Tag
    {
        return new Tag($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function todos(): Todo
    {
        return new Todo($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    public function users(): User
    {
        return new User($this->requestBuilder, $this->responseHandler, $this->jsonCodec);
    }

    /**
     * Direct access to the underlying authenticator for power users (e.g.
     * issuing ad-hoc tokens, exchanging an ownership-id for a raw bearer
     * token out of band).
     */
    public function authenticator(): OrgaMaxAuthenticator
    {
        return $this->authenticator;
    }

    /**
     * Walk every page of a list endpoint and yield each item as it arrives.
     *
     * Pass any callable that accepts `(int $limit, int $offset)` and returns
     * a {@see ListResponse} - usually a closure over a resource's `list()`
     * method. Iteration stops when the server signals it has no more rows
     * (either `items` is shorter than the requested page, or the running
     * offset reaches the reported `total`).
     *
     *     foreach ($client->paginate(
     *         fn (int $l, int $o) => $client->customers()->list(limit: $l, offset: $o),
     *         pageSize: 100,
     *     ) as $customer) {
     *         // ...
     *     }
     *
     * @param callable(int, int): ListResponse $listCall
     *
     * @return Generator<int, array<string, mixed>, void, void>
     */
    public function paginate(callable $listCall, int $pageSize = self::DEFAULT_PAGE_SIZE): Generator
    {
        return Pagination::walk($listCall, $pageSize);
    }
}
