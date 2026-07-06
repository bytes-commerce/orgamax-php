<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Todo;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Todo::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class TodoTest extends TestCase
{
    private FakeHttpClient $client;

    private Todo $todos;

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
        $this->todos = new Todo(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testCreatePostsToTodo(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 't1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->todos->create([
            'todo' => [
                'title' => 'Call ACME',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/todo/', $sent->getUri()->getPath());
    }

    public function testAddMessagePostsToMessageEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->todos->addMessage('t1', [
            'text' => 'Follow up',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/todo/t1/message', $sent->getUri()->getPath());
    }

    public function testDeleteRemoves(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(204, ''));
        $this->todos->delete('t1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('DELETE', $sent->getMethod());
        self::assertSame('/openapi/todo/t1', $sent->getUri()->getPath());
    }

    public function testMessagesHitsGetEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->todos->messages('t1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/todo/t1', $sent->getUri()->getPath());
    }

    public function testSetDatePuts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->todos->setDate('t1', [
            'dueDate' => '2026-08-01',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/todo/t1', $sent->getUri()->getPath());
    }

    public function testDeleteMessagePosts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(204, ''));
        $this->todos->deleteMessage('msg1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/todo/message/msg1', $sent->getUri()->getPath());
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
        $this->todos->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/todo', $sent->getUri()->getPath());
    }

    public function testLinkPuts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->todos->link('t1', [
            'entityType' => 'order',
            'entityId' => 'o1',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/todo/t1/link', $sent->getUri()->getPath());
    }

    public function testUnlinkPuts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->todos->unlink('t1', [
            'entityType' => 'order',
            'entityId' => 'o1',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/todo/t1/unlink', $sent->getUri()->getPath());
    }

    public function testSetStatusPuts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->todos->setStatus('t1', [
            'status' => 'done',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/todo/t1/status', $sent->getUri()->getPath());
    }
}
