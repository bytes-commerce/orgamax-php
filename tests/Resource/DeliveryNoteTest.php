<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\DeliveryNote;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeliveryNote::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class DeliveryNoteTest extends TestCase
{
    private FakeHttpClient $client;

    private DeliveryNote $deliveryNotes;

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
        $this->deliveryNotes = new DeliveryNote(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testDownloadDocumentHitsBinaryEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, '%PDF-1.4 fake content', [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->deliveryNotes->downloadDocument('dn1');

        self::assertSame(200, $response->statusCode);
        self::assertSame('application/pdf', $response->contentType);
        self::assertStringContainsString('%PDF-1.4', $response->toString());

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/deliveryNote/document/dn1', $sent->getUri()->getPath());
    }

    public function testGetBuildsPath(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'dn1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->deliveryNotes->get('dn1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/deliveryNote/dn1', $sent->getUri()->getPath());
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
        $this->deliveryNotes->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/deliveryNote', $sent->getUri()->getPath());
    }
}
