<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Expense;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Expense::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class ExpenseTest extends TestCase
{
    private FakeHttpClient $client;

    private Expense $expenses;

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
        $this->expenses = new Expense(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testCreatePostsToExpense(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'e1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->expenses->create([
            'expense' => [
                'amount' => 12.5,
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/expense/', $sent->getUri()->getPath());
    }

    public function testDeleteRemoves(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(204, ''));
        $this->expenses->delete('e1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('DELETE', $sent->getMethod());
        self::assertSame('/openapi/expense/e1', $sent->getUri()->getPath());
    }

    public function testGetBuildsPath(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'e1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->expenses->get('e1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/expense/e1', $sent->getUri()->getPath());
    }

    public function testUpdatePutBody(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->expenses->update('e1', [
            'expense' => [
                'amount' => 99,
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/expense/e1', $sent->getUri()->getPath());
        self::assertStringContainsString('"amount":99', (string) $sent->getBody());
    }

    public function testDeleteReceiptHitsReceiptEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(204, ''));
        $this->expenses->deleteReceipt('rec1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('DELETE', $sent->getMethod());
        self::assertSame('/openapi/expense/receipt/rec1', $sent->getUri()->getPath());
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
        $this->expenses->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/expense', $sent->getUri()->getPath());
    }
}
