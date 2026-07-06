<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Customer;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Customer::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class CustomerTest extends TestCase
{
    private FakeHttpClient $client;

    private Customer $customers;

    #[Override]
    protected function setUp(): void
    {
        $psr17 = new Psr17Factory();
        $this->client = new FakeHttpClient();
        $tokenProvider = new StaticTokenProvider('test-token');
        $requestBuilder = new RequestBuilder(
            baseUrl: 'https://api.orgamax.de/openapi',
            requestFactory: $psr17,
            streamFactory: $psr17,
            tokenProvider: $tokenProvider,
        );
        $responseHandler = new ResponseHandler($this->client);
        $this->customers = new Customer(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testCreatePostsToCustomer(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'c1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->customers->create([
            'customer' => [
                'name' => 'ACME',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/customer/', $sent->getUri()->getPath());
    }

    public function testUpdatePutBody(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->customers->update('c1', [
            'customer' => [
                'name' => 'New',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/customer/c1', $sent->getUri()->getPath());
        self::assertStringContainsString('"name":"New"', (string) $sent->getBody());
    }

    public function testGetBuildsPath(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'c1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->customers->get('c1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/customer/c1', $sent->getUri()->getPath());
    }

    public function testListBuildsQuery(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [
                'total' => 0,
                'limit' => 25,
                'offset' => 0,
            ],
        ], \JSON_THROW_ON_ERROR)));
        $this->customers->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/customer', $sent->getUri()->getPath());
        $query = [];
        parse_str($sent->getUri()->getQuery(), $query);
        self::assertSame('25', $query['limit'] ?? null);
    }

    public function testUpsertWithoutIdCreates(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'c-new',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->customers->upsert([
            'customer' => [
                'name' => 'New Co',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/customer/', $sent->getUri()->getPath());
    }

    public function testUpsertWithIdUpdates(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->customers->upsert([
            'customer' => [
                'name' => 'Updated',
            ],
        ], id: 'c1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/customer/c1', $sent->getUri()->getPath());
    }

    public function testUpsertWithEmptyStringIdCreates(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'c-new',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->customers->upsert([
            'customer' => [
                'name' => 'New',
            ],
        ], id: '');

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod(), 'empty string id should fall back to create');
        self::assertSame('/openapi/customer/', $sent->getUri()->getPath());
    }
}
