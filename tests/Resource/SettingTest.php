<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Resource;

use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Resource\Setting;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Setting::class)]
#[CoversClass(RequestBuilder::class)]
#[CoversClass(ResponseHandler::class)]
#[CoversClass(JsonCodec::class)]
final class SettingTest extends TestCase
{
    private FakeHttpClient $client;

    private Setting $settings;

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
        $this->settings = new Setting(
            requestBuilder: $requestBuilder,
            responseHandler: $responseHandler,
            jsonCodec: new JsonCodec(),
        );
    }

    public function testAccountHitsAccountEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->settings->account();

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/setting/account', $sent->getUri()->getPath());
    }

    public function testMiscellaneousHitsMiscEndpoint(): void
    {
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [],
        ], \JSON_THROW_ON_ERROR)));
        $this->settings->miscellaneous();

        $sent = $this->client->sentRequests[0];
        self::assertSame('GET', $sent->getMethod());
        self::assertSame('/openapi/setting/miscellaneous', $sent->getUri()->getPath());
    }
}
