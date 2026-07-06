<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Invoice;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Invoice::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class InvoiceTest extends TestCase
{
    private FakeHttpClient $client;

    private Invoice $invoices;

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
        $this->invoices = new Invoice(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testAddPaymentPosts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->invoices->addPayment('inv1', [
            'amount' => 100,
            'date' => '2026-07-06',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/invoice/inv1/payment', $sent->getUri()->getPath());
    }

    public function testDownloadDocumentReturnsBinary(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, '%PDF-inv', [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->invoices->downloadDocument('inv1');

        self::assertSame('application/pdf', $response->contentType);
        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/invoice/document/inv1', $sent->getUri()->getPath());
    }

    public function testDownloadHitsDeprecatedEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, '%PDF-inv', [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->invoices->download('inv1');

        self::assertStringContainsString('%PDF-inv', $response->toString());
        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/invoice/inv1/download', $sent->getUri()->getPath());
    }

    public function testGetBuildsPath(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'inv1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->invoices->get('inv1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/invoice/inv1', $sent->getUri()->getPath());
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
        $this->invoices->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/invoice', $sent->getUri()->getPath());
    }

    public function testLockPuts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->invoices->lock('inv1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/invoice/inv1/lock', $sent->getUri()->getPath());
    }

    public function testSendPosts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->invoices->send('inv1', [
            'to' => 'a@b.de',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/invoice/inv1/send', $sent->getUri()->getPath());
        self::assertStringContainsString('"to":"a@b.de"', (string) $sent->getBody());
    }
}
