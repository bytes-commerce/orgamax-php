<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Article;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Article::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class ArticleTest extends TestCase
{
    private FakeHttpClient $client;

    private Article $articles;

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
        $this->articles = new Article(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testCreatePostsToArticle(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [[
                'id' => 'a1',
                'title' => 'X',
            ]],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $result = $this->articles->create([
            'article' => [
                'title' => 'My Article',
                'unit' => 'Stk.',
                'number' => '0015',
                'price' => 200.25,
                'vatPercent' => 19,
            ],
        ]);

        self::assertNotNull($result->first());
        self::assertCount(1, $this->client->sentRequests);
        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/article/', $sent->getUri()->getPath());
        self::assertSame('Bearer test-token', $sent->getHeaderLine('Authorization'));
        self::assertSame('application/json', $sent->getHeaderLine('Content-Type'));
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
        $this->articles->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/article/', $sent->getUri()->getPath());
        // Verify limit appears in query
        $query = [];
        parse_str($sent->getUri()->getQuery(), $query);
        self::assertSame('25', $query['limit'] ?? null);
        self::assertSame('0', $query['offset'] ?? null);
    }

    public function testGetBuildsPath(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [[
                'id' => 'abc',
            ]],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->articles->get('abc');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/article/abc', $sent->getUri()->getPath());
    }

    public function testUpdatePutBody(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->articles->update('xyz', [
            'article' => [
                'title' => 'new',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/article/xyz', $sent->getUri()->getPath());
        $body = (string) $sent->getBody();
        self::assertStringContainsString('"title":"new"', $body);
    }

    public function testDeleteRemoves(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(204, ''));

        $this->articles->delete('xyz');

        $sent = $this->client->sentRequests[0];
        self::assertSame('DELETE', $sent->getMethod());
        self::assertSame('/openapi/article/xyz', $sent->getUri()->getPath());
    }

    public function testCreateSettingHitsSettingArticle(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->articles->createSetting([
            'units' => 'Stck., Liter',
            'categories' => 'Zubehör',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/setting/article', $sent->getUri()->getPath());
    }

    public function testUpsertWithoutIdCreates(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'a-new',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->articles->upsert([
            'article' => [
                'title' => 'New Article',
                'number' => '9999',
            ],
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/article/', $sent->getUri()->getPath());
    }

    public function testUpsertWithIdUpdates(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));

        $this->articles->upsert([
            'article' => [
                'title' => 'Updated',
            ],
        ], id: 'abc');

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/article/abc', $sent->getUri()->getPath());
    }
}
