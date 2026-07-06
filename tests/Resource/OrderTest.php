<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Order;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Order::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class OrderTest extends TestCase
{
    private FakeHttpClient $client;

    private Order $orders;

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
        $this->orders = new Order(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testCreateInvoiceDraftsInvoiceFromOrder(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'inv-drafted',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $result = $this->orders->createInvoice('order1', [
            'date' => '2026-07-06',
        ]);

        self::assertSame('inv-drafted', $result->data['id'] ?? null);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/order/order1/invoice', $sent->getUri()->getPath());
        self::assertSame('Bearer test-token', $sent->getHeaderLine('Authorization'));
        self::assertStringContainsString('"date":"2026-07-06"', (string) $sent->getBody());
    }

    public function testCreatePostsOrder(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'o1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->orders->create([
            'order' => [
                'customerId' => 'c1',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/order/', $sent->getUri()->getPath());
    }

    public function testDownloadDocumentReturnsBinary(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, '%PDF-order', [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->orders->downloadDocument('o1');

        self::assertSame('application/pdf', $response->contentType);
        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/order/document/o1', $sent->getUri()->getPath());
    }

    public function testGetBuildsPath(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'o1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->orders->get('o1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/order/o1', $sent->getUri()->getPath());
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
        $this->orders->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/order', $sent->getUri()->getPath());
    }
}
