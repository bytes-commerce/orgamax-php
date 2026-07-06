# bytes-commerce/orgamax-php

Modern, framework-agnostic **PHP 8.3+** client for the
[OrgaMax Cloud](https://app.orgamax.de) OpenAPI.

> **Vendor:** Bytes Commerce (bytes-commerce.de)
> **License:** MIT
> **Coverage:** every operation published by the
> [OrgaMax OpenAPI spec](https://api.orgamax.de/openapi/documentation/).

---

## Why this library?

You hand the client your `apiKey`, `apiSecret`, and `ownershipId` **once**
in a factory call. From that point on, every resource method
(`$client->articles()->list(...)`,
`$client->invoices()->get(...)`, …) reuses the cached bearer token, the
shared HTTP client, and the shared PSR-17 factories. **No per-call
plumbing**.

Three factory styles are available so you can pick the one that fits
your code:

| Style                                                      | When to reach for it                                          |
|------------------------------------------------------------|---------------------------------------------------------------|
| `OrgaMaxClientBuilder::create()->...->build()`             | Application code: fluent, descriptive, easy to extend         |
| `OrgaMaxClient::create(apiKey:, apiSecret:, ownershipId:)` | Tiny one-liners, scripts, throwaway demos                     |
| `OrgaMaxClient::fromConfig(OrgaMaxConfig::default(...))`   | DI containers (Symfony, Laravel): immutable config, shareable |

---

## Requirements

- PHP `^8.3`
- A working **PSR-18** HTTP client (Guzzle 7, Symfony HttpClient PSR-18
  adapter, `nyholm/psr7` + `php-http/discovery`, …)
- A **PSR-17** factory pair (`nyholm/psr7`, `guzzle/psr7`, Laminas, ...) or
  rely on `php-http/discovery`
- Optional: a **PSR-16** cache for the bearer token
  (`symfony/cache`, `predis/predis`, ...). Otherwise an in-memory cache is
  used

## Install

```bash
composer require bytes-commerce/orgamax-php
```

---

## Quick start

### 1. Fluent builder (recommended)

```php
use BytesCommerce\Orgamax\OrgaMaxClient;

$client = OrgaMaxClient::builder()
    ->withApiKey(getenv('ORGAMAX_API_KEY'))
    ->withApiSecret(getenv('ORGAMAX_API_SECRET'))
    ->withOwnershipId(getenv('ORGAMAX_OWNERSHIP_ID'))
    ->build();

$articles = $client->articles()->list(limit: 50);
$invoice  = $client->invoices()->get(id: '507f1f77bcf86cd799439011');
```

### 2. Named-arg factory

```php
use BytesCommerce\Orgamax\OrgaMaxClient;

$client = OrgaMaxClient::create(
    apiKey:      getenv('ORGAMAX_API_KEY'),
    apiSecret:   getenv('ORGAMAX_API_SECRET'),
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID'),
);
```

### 3. Immutable config (DI container)

```php
use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\OrgaMaxConfig;

$config = OrgaMaxConfig::default(
    apiKey:      getenv('ORGAMAX_API_KEY'),
    apiSecret:   getenv('ORGAMAX_API_SECRET'),
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID'),
);

$client = OrgaMaxClient::fromConfig($config);
```

`OrgaMaxConfig` is `final readonly` and its `with*()` methods return a
new instance. Share it across services, freeze it at boot, and you are
guaranteed it cannot be mutated downstream.

---

## Auth flow

OrgaMax Cloud uses a two-step handshake that matches the
[`/auth/token` operation](https://api.orgamax.de/openapi/documentation/):

1. **HTTP Basic** auth with `apiKey` + `apiSecret` → call
   `POST /openapi/auth/token` with body `{ "ownershipId": "..." }`.
2. The response carries `{ "token": "...", "expires_in": ... }`. That
   bearer token is attached as `Authorization: Bearer ...` to every
   subsequent call.

The bundled `CachedTokenProvider` stores the bearer token under
`orgamax.token.{ownershipId}` in your PSR-16 cache, so you only pay the
handshake once per TTL window (default 24 h).

`StaticTokenProvider` lets you inject a pre-minted token: handy for
test suites or for tokens issued by a sidecar process.

```php
use BytesCommerce\Orgamax\OrgaMaxClient;

$client = OrgaMaxClient::builder()
    ->withApiKey(getenv('ORGAMAX_API_KEY'))
    ->withApiSecret(getenv('ORGAMAX_API_SECRET'))
    ->withOwnershipId(getenv('ORGAMAX_OWNERSHIP_ID'))
    ->withStaticToken($mintFromVault)
    ->build();
```

---

## Configuration reference

| Field            | Required | Default                                 | What to override it with           |
|------------------|----------|-----------------------------------------|------------------------------------|
| `apiKey`         | yes      | _none_                                  | OrgaMax Cloud API key              |
| `apiSecret`      | yes      | _none_                                  | OrgaMax Cloud API secret           |
| `ownershipId`    | yes      | _none_                                  | The ownership / tenant ID          |
| `baseUrl`        | no       | `https://api.orgamax.de/openapi`        | Staging / on-prem gateway          |
| `httpClient`     | no       | `php-http/discovery` PSR-18 lookup      | Guzzle 7, Symfony HttpClient, …    |
| `requestFactory` | no       | `php-http/discovery` PSR-17 lookup      | `nyholm/psr7`, `guzzle/psr7`, …    |
| `streamFactory`  | no       | `php-http/discovery` PSR-17 lookup      | `nyholm/psr7`, `guzzle/psr7`, …    |
| `tokenCache`     | no       | `InMemoryTokenCache` (process-local)    | Redis, filesystem, memcached       |
| `tokenProvider`  | no       | `CachedTokenProvider::withDefaultTtl()` | `StaticTokenProvider`, custom impl |

### Customisation examples

```php
use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\OrgaMaxClient;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpClient\Psr18Client;

// Bring your own HTTP client (Symfony's PSR-18 bridge):
$http = Psr18Client::create(['timeout' => 5]);

// Bring your own PSR-16 cache so tokens survive across requests:
$cache = new RedisAdapter(new \Redis(), 'orgamax', 86400);

$client = OrgaMaxClient::builder()
    ->withApiKey('...')
    ->withApiSecret('...')
    ->withOwnershipId('...')
    ->withHttpClient($http)
    ->withTokenCache($cache)
    ->build();
```

Override the token provider entirely (e.g. for static tokens, vault
lookups, sidecar minting):

```php
$client = OrgaMaxClient::builder()
    ->withApiKey('...')
    ->withApiSecret('...')
    ->withOwnershipId('...')
    ->withTokenProvider(new StaticTokenProvider($vault->get('orgamax-jwt')))
    ->build();
```

---

## Resource map

Every endpoint in the OpenAPI spec has a strongly-typed accessor:

| Resource                          | Accessor on `$client`           |
|-----------------------------------|---------------------------------|
| Article                           | `$client->articles()`           |
| Authenticator (raw `/auth/token`) | `$client->authenticator()`      |
| Bookkeeping (chart of accounts)   | `$client->bookkeepings()`       |
| Customer                          | `$client->customers()`          |
| DeliveryCondition                 | `$client->deliveryConditions()` |
| DeliveryNote                      | `$client->deliveryNotes()`      |
| Expense                           | `$client->expenses()`           |
| File                              | `$client->files()`              |
| Invoice                           | `$client->invoices()`           |
| Offer                             | `$client->offers()`             |
| Order                             | `$client->orders()`             |
| PayCondition                      | `$client->payConditions()`      |
| Setting                           | `$client->settings()`           |
| Supplier                          | `$client->suppliers()`          |
| Tag                               | `$client->tags()`               |
| Todo                              | `$client->todos()`              |
| User                              | `$client->users()`              |

---

## Examples

A few common write paths to get you started. For runnable, copy-paste
scripts see [`examples/`](examples/README.md):

| File                              | What it shows                                       |
|-----------------------------------|-----------------------------------------------------|
| `01-quickstart.php`               | three factory styles for building the client        |
| `02-customers.php`                | customer CRUD and `upsert()`                        |
| `03-orders-invoices.php`          | order → invoice flow (`createInvoice`, send, lock)  |
| `04-articles-suppliers.php`       | article / supplier writes and per-article settings  |
| `05-error-handling.php`           | catching every exception type                       |
| `06-pagination.php`               | walking every page with `$client->paginate()`       |
| `07-retry.php`                    | retrying transient failures with `RetryHandler`     |
| `08-custom-cache.php`             | swapping in a Redis / APCu PSR-16 token cache       |

### Create a customer

`POST /customer/` accepts the full customer record and returns the new
record (with the assigned id) in `data`.

```php
$response = $client->customers()->create([
    'customer' => [
        'name'           => 'ACME GmbH',
        'customerNumber' => 'C-1001',
        'email'          => 'buchhaltung@acme.example',
        'addresses' => [[
            'street'      => 'Musterstr. 12',
            'zip'         => '12345',
            'city'        => 'Berlin',
            'countryCode' => 'DE',
        ]],
    ],
]);

$newId = $response->first()['id'] ?? null;
```

### Update a customer

`PUT /customer/{id}` overwrites the customer record. Pass the fields you
want to keep; the API treats the payload as the new state.

```php
$client->customers()->update('c-1001', [
    'customer' => [
        'email' => 'neu@acme.example',
    ],
]);
```

### Upsert a customer (create-or-update)

`Customer::upsert()` saves you the branching: pass an id and it
`PUT`s, omit it (or pass `''`) and it `POST`s.

```php
$client->customers()->upsert(
    payload: ['customer' => ['name' => 'ACME GmbH']],
    id: $existingId, // or null to create
);
```

The same convenience exists on `Article` and `Supplier`.

### Create an order

Orders are the primary sales document in OrgaMax and they are the only
way to drive the invoice lifecycle.

```php
$order = $client->orders()->create([
    'order' => [
        'customerId' => 'c-1001',
        'orderDate'  => '2026-07-06',
        'positions'  => [[
            'articleId' => 'a-42',
            'quantity'  => 2,
            'price'     => 199.00,
        ]],
    ],
]);

$orderId = $order->first()['id'];
```

### Create an invoice from an order

Invoices are **never** created directly via `POST /invoice/`. The
OrgaMax OpenAPI does not expose that endpoint. Instead, you draft an
invoice by posting to the order that owns it.

```php
$invoice = $client->orders()->createInvoice(
    orderId: $orderId,
    payload: [
        'invoiceDate' => '2026-07-06',
        'dueDate'     => '2026-07-20',
    ],
);

$invoiceId = $invoice->first()['id'];

$client->invoices()->send($invoiceId, [
    'method' => 'email',
    'to'     => 'buchhaltung@acme.example',
]);
```

After the invoice exists you can lock it, download the document, record
a payment, and look it up the usual way:

```php
$client->invoices()->lock($invoiceId);
$pdf = $client->invoices()->downloadDocument($invoiceId);

$client->invoices()->addPayment($invoiceId, [
    'amount'      => 199.00,
    'paidAt'      => '2026-07-15',
    'paymentType' => 'bankTransfer',
]);

$full = $client->invoices()->get($invoiceId);
```

### A note on Users

The OrgaMax OpenAPI exposes Users as a **read-only** resource. Only
`GET /user` is published, so `User::list()` is the only available call
on `$client->users()`. User accounts are provisioned in the OrgaMax
admin UI; this client cannot create, update, or delete them.

---

## Error handling

Every API call can throw one of the following. All live under
`BytesCommerce\Orgamax\Exception\`:

| Exception                 | When it fires                                                 |
|---------------------------|---------------------------------------------------------------|
| `AuthenticationException` | `/auth/token` returned non-2xx or no `token` field            |
| `RateLimitException`      | `429 Too Many Requests` (includes `Retry-After` hint)         |
| `OrgaMaxException`        | any other non-2xx response (4xx / 5xx) with parsed error body |
| `TransportException`      | PSR-18 client threw (network down, DNS, TLS, …)               |

```php
use BytesCommerce\Orgamax\Exception\AuthenticationException;
use BytesCommerce\Orgamax\Exception\RateLimitException;
use BytesCommerce\Orgamax\Exception\TransportException;

try {
    $invoice = $client->invoices()->get(id: '507f1f77bcf86cd799439011');
} catch (AuthenticationException $e) {
    // re-mint a token, or surface the credential issue
} catch (RateLimitException $e) {
    sleep($e->retryAfterSeconds);
    // retry
} catch (TransportException $e) {
    // bubble up to your retry layer
}
```

---

## Testing

The library ships with PSR-7 / PSR-18 / `ClientExceptionInterface` test
doubles under `tests/Fixtures/TestDouble/`:

- `FakeHttpClient`: queue responses, assert on sent requests
- `FakeResponse` / `FakeResponseWithBody`: PSR-7 responses with bodies
- `FakeResponseFactory`: PSR-17 factory
- `FakeStream`: PSR-7 stream
- `FakeClientException`: PSR-18 transport failure

Wire them in via the builder or constructor:

```php
use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeHttpClient;
use BytesCommerce\Orgamax\Tests\Fixtures\TestDouble\FakeResponseWithBody;
use Nyholm\Psr7\Factory\Psr17Factory;

$http = new FakeHttpClient();
$http->enqueue(new FakeResponseWithBody(200, json_encode([
    'token' => 'fake-jwt',
    'expires_in' => 86400,
], JSON_THROW_ON_ERROR)));

$client = OrgaMaxClient::builder()
    ->withApiKey('k')
    ->withApiSecret('s')
    ->withOwnershipId('o')
    ->withHttpClient($http)
    ->withRequestFactory(new Psr17Factory())
    ->withStreamFactory(new Psr17Factory())
    ->build();
```

---

## Contributing

```bash
composer install
composer test          # PHPUnit 11
composer phpstan       # PHPStan MAX (phpstan-strict + ergebnis/phpstan-rules)
composer ecs           # ECS: code style
composer rector:dry    # Rector: refactor suggestions (review the diff before applying)
```

The CI workflow (`.github/workflows/ci.yml`) runs all four checks on PHP
8.3 and 8.4 against the lowest and highest dependency resolutions.

PRs are welcome.

## License

MIT, see [LICENSE](./LICENSE).