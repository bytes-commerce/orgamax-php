<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests;

use BytesCommerce\Orgamax\Auth\OrgaMaxAuthenticator;
use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\Resource\Article;
use BytesCommerce\Orgamax\Resource\Bookkeeping;
use BytesCommerce\Orgamax\Resource\Customer;
use BytesCommerce\Orgamax\Resource\DeliveryCondition;
use BytesCommerce\Orgamax\Resource\DeliveryNote;
use BytesCommerce\Orgamax\Resource\Expense;
use BytesCommerce\Orgamax\Resource\File;
use BytesCommerce\Orgamax\Resource\Invoice;
use BytesCommerce\Orgamax\Resource\Offer;
use BytesCommerce\Orgamax\Resource\Order;
use BytesCommerce\Orgamax\Resource\PayCondition;
use BytesCommerce\Orgamax\Resource\Setting;
use BytesCommerce\Orgamax\Resource\Supplier;
use BytesCommerce\Orgamax\Resource\Tag;
use BytesCommerce\Orgamax\Resource\Todo;
use BytesCommerce\Orgamax\Resource\User;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrgaMaxClient::class)]
final class OrgaMaxClientTest extends TestCase
{
    private FakeHttpClient $client;

    private Psr17Factory $psr17;

    #[Override]
    protected function setUp(): void
    {
        $this->client = new FakeHttpClient();
        $this->psr17 = new Psr17Factory();
    }

    public function testConstructsAllResources(): void
    {
        $client = $this->makeClient();

        self::assertInstanceOf(Article::class, $client->articles());
        self::assertInstanceOf(Bookkeeping::class, $client->bookkeepings());
        self::assertInstanceOf(Customer::class, $client->customers());
        self::assertInstanceOf(DeliveryCondition::class, $client->deliveryConditions());
        self::assertInstanceOf(DeliveryNote::class, $client->deliveryNotes());
        self::assertInstanceOf(Expense::class, $client->expenses());
        self::assertInstanceOf(File::class, $client->files());
        self::assertInstanceOf(Invoice::class, $client->invoices());
        self::assertInstanceOf(Offer::class, $client->offers());
        self::assertInstanceOf(Order::class, $client->orders());
        self::assertInstanceOf(PayCondition::class, $client->payConditions());
        self::assertInstanceOf(Setting::class, $client->settings());
        self::assertInstanceOf(Supplier::class, $client->suppliers());
        self::assertInstanceOf(Tag::class, $client->tags());
        self::assertInstanceOf(Todo::class, $client->todos());
        self::assertInstanceOf(User::class, $client->users());
    }

    public function testAuthenticatorIsExposedDirectly(): void
    {
        $client = $this->makeClient();

        self::assertInstanceOf(OrgaMaxAuthenticator::class, $client->authenticator());
    }

    public function testCreateFactoryMethodAcceptsInMemoryCache(): void
    {
        $client = new OrgaMaxClient(
            apiKey: 'k',
            apiSecret: 's',
            ownershipId: '999',
            httpClient: $this->client,
            requestFactory: $this->psr17,
            streamFactory: $this->psr17,
            tokenProvider: new StaticTokenProvider('test-token'),
            baseUrl: 'https://api.orgamax.de/openapi',
        );

        self::assertInstanceOf(Article::class, $client->articles());
    }

    public function testDefaultPageSizeIsDocumented(): void
    {
        self::assertSame(50, OrgaMaxClient::DEFAULT_PAGE_SIZE);
    }

    public function testPaginateYieldsAcrossMultiplePages(): void
    {
        $client = $this->makeClient();

        // Page 1: 2 items, total=3
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [[
                'id' => 'a',
            ], [
                'id' => 'b',
            ]],
            'meta' => [
                'total' => 3,
                'limit' => 2,
                'offset' => 0,
            ],
        ], \JSON_THROW_ON_ERROR)));
        // Page 2: 1 item, total=3
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [[
                'id' => 'c',
            ]],
            'meta' => [
                'total' => 3,
                'limit' => 2,
                'offset' => 2,
            ],
        ], \JSON_THROW_ON_ERROR)));

        $ids = [];
        foreach ($client->paginate(
            fn (int $limit, int $offset): ListResponse => $client->users()->list(limit: $limit, offset: $offset),
            pageSize: 2,
        ) as $row) {
            $ids[] = $row['id'];
        }

        self::assertSame(['a', 'b', 'c'], $ids);
        self::assertCount(2, $this->client->sentRequests);
    }

    public function testPaginateStopsOnEmptyPage(): void
    {
        $client = $this->makeClient();

        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [],
            'meta' => [
                'total' => 0,
                'limit' => 10,
                'offset' => 0,
            ],
        ], \JSON_THROW_ON_ERROR)));

        $count = 0;
        foreach ($client->paginate(
            fn (int $limit, int $offset): ListResponse => $client->users()->list(limit: $limit, offset: $offset),
        ) as $row) {
            $count++;
        }

        self::assertSame(0, $count);
        self::assertCount(1, $this->client->sentRequests);
    }

    public function testPaginateWalksFullTotal(): void
    {
        $client = $this->makeClient();

        // 3 pages of 2 items each, total=5 (last page is partial)
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [[
                'id' => 1,
            ], [
                'id' => 2,
            ]],
            'meta' => [
                'total' => 5,
                'limit' => 2,
                'offset' => 0,
            ],
        ], \JSON_THROW_ON_ERROR)));
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [[
                'id' => 3,
            ], [
                'id' => 4,
            ]],
            'meta' => [
                'total' => 5,
                'limit' => 2,
                'offset' => 2,
            ],
        ], \JSON_THROW_ON_ERROR)));
        $this->client->enqueue(new FakeResponseWithBody(200, json_encode([
            'data' => [[
                'id' => 5,
            ]],
            'meta' => [
                'total' => 5,
                'limit' => 2,
                'offset' => 4,
            ],
        ], \JSON_THROW_ON_ERROR)));

        $ids = [];
        foreach ($client->paginate(
            fn (int $limit, int $offset): ListResponse => $client->users()->list(limit: $limit, offset: $offset),
            pageSize: 2,
        ) as $row) {
            $ids[] = $row['id'];
        }

        self::assertSame([1, 2, 3, 4, 5], $ids);
    }

    private function makeClient(): OrgaMaxClient
    {
        return new OrgaMaxClient(
            apiKey: 'key',
            apiSecret: 'secret',
            ownershipId: '12345',
            httpClient: $this->client,
            requestFactory: $this->psr17,
            streamFactory: $this->psr17,
            tokenProvider: new StaticTokenProvider('test-token'),
            baseUrl: 'https://api.orgamax.de/openapi',
        );
    }
}
