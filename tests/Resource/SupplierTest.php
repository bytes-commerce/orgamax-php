<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Supplier;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Supplier::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class SupplierTest extends TestCase
{
    private FakeHttpClient $client;

    private Supplier $suppliers;

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
        $this->suppliers = new Supplier(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testCreatePostsToSupplier(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 's1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->suppliers->create([
            'supplier' => [
                'name' => 'ACME',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/supplier', $sent->getUri()->getPath());
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
        $this->suppliers->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/supplier', $sent->getUri()->getPath());
    }

    public function testDeleteRemoves(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(204, ''));
        $this->suppliers->delete('s1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('DELETE', $sent->getMethod());
        self::assertSame('/openapi/supplier/s1', $sent->getUri()->getPath());
    }

    public function testUpdatePutBody(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->suppliers->update('s1', [
            'supplier' => [
                'name' => 'New',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/supplier/s1', $sent->getUri()->getPath());
        self::assertStringContainsString('"name":"New"', (string) $sent->getBody());
    }

    public function testGetBuildsPath(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 's1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->suppliers->get('s1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/supplier/s1', $sent->getUri()->getPath());
    }

    public function testUpsertWithoutIdCreates(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 's-new',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->suppliers->upsert([
            'supplier' => [
                'name' => 'New Vendor',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/supplier', $sent->getUri()->getPath());
    }

    public function testUpsertWithIdUpdates(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->suppliers->upsert([
            'supplier' => [
                'name' => 'Updated',
            ],
        ], id: 's1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/supplier/s1', $sent->getUri()->getPath());
    }
}
