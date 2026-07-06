<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\File;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(File::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class FileTest extends TestCase
{
    private FakeHttpClient $client;

    private File $files;

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
        $this->files = new File(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testAnalyzeHitsAnalyzeEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'status' => 'queued',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->files->analyze('f1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/file/f1/analyze', $sent->getUri()->getPath());
    }

    public function testDeleteRemoves(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(204, ''));
        $this->files->delete('f1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('DELETE', $sent->getMethod());
        self::assertSame('/openapi/file/f1', $sent->getUri()->getPath());
    }

    public function testDownloadReturnsBinary(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, 'binary-content', [
            'Content-Type' => 'application/octet-stream',
        ]));

        $response = $this->files->download('f1');

        self::assertSame('application/octet-stream', $response->contentType);
        self::assertSame('binary-content', $response->toString());

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/file/f1', $sent->getUri()->getPath());
    }

    public function testUpdateMetaPut(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->files->updateMeta('f1', [
            'name' => 'new-name.pdf',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('PUT', $sent->getMethod());
        self::assertSame('/openapi/file/f1/meta', $sent->getUri()->getPath());
        self::assertStringContainsString('"name":"new-name.pdf"', (string) $sent->getBody());
    }

    public function testInfoHitsMetaEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'f1',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->files->info('f1');

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/file/f1/meta', $sent->getUri()->getPath());
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
        $this->files->list(limit: 25, offset: 0);

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/file', $sent->getUri()->getPath());
    }

    public function testUploadPosts(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [
                'id' => 'new',
            ],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->files->upload([
            'filename' => 'doc.pdf',
            'content' => 'base64-content',
        ]);

        $sent = $this->client->sentRequests[0];
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('/openapi/file/upload', $sent->getUri()->getPath());
        self::assertStringContainsString('"filename":"doc.pdf"', (string) $sent->getBody());
    }
}
