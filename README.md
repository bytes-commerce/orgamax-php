# bytes-commerce/orgamax-php

Modern, framework-agnostic **PHP 8.3+** client for the [OrgaMax Cloud](https://app.orgamax.de) OpenAPI.

> **Vendor:** Bytes Commerce (bytes-commerce.de)  
> **License:** MIT  
> **Coverage:** every operation published by the [OrgaMax OpenAPI spec](https://api.orgamax.de/openapi/documentation/).

---

## Requirements

- PHP `^8.3`
- A working PSR-18 HTTP client (Guzzle 7, Symfony HttpClient PSR-18 adapter, nyholm/psr7 + php-http/discovery, …)
- A PSR-16 cache (or use `StaticTokenProvider` and handle caching yourself)

## Install

```bash
composer require bytes-commerce/orgamax-php
```

## Quick start

```php
<?php

use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\Auth\CachedTokenProvider;
use BytesCommerce\Orgamax\Auth\StaticTokenProvider;
use BytesCommerce\Orgamax\Model\ArticleData;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$client = new OrgaMaxClient(
    apiKey:         getenv('ORGAMAX_API_KEY'),
    apiSecret:      getenv('ORGAMAX_API_SECRET'),
    ownershipId:    getenv('ORGAMAX_OWNERSHIP_ID'),
    httpClient:     \Http\Discovery\Psr18ClientDiscovery::find(),
    requestFactory: \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory(),
    tokenProvider:  new CachedTokenProvider(
        cache: new FilesystemAdapter('orgamax'),
        ownershipId: getenv('ORGAMAX_OWNERSHIP_ID'),
        ttlSeconds: 86400,
    ),
);

$paginated = $client->articles()->list(limit: 50);
$article   = $client->articles()->get(id: '507f1f77bcf86cd799439011');
```

## Auth flow

OrgaMax Cloud uses a two-step handshake:

1. With **HTTP Basic** (`apiKey` + `apiSecret`), call `POST /auth/token` with your `ownershipId`.
2. Use the returned **bearer token** on every other request.

`CachedTokenProvider` stores the bearer token in your PSR-16 cache under the key `orgamax.token.{ownershipId}` so you only pay the handshake once per TTL window. `StaticTokenProvider` lets you provide a pre-minted token you fetched yourself.

## Resource map

| Service | Method on `$client` |
|---|---|
| Article | `$client->articles()` |
| Authenticator | `$client->authenticator()` |
| Bookkeeping | `$client->bookkeeping()` |
| Customer | `$client->customers()` |
| DeliveryNote | `$client->deliveryNotes()` |
| Expense | `$client->expenses()` |
| File | `$client->files()` |
| Invoice | `$client->invoices()` |
| Offer | `$client->offers()` |
| Order | `$client->orders()` |
| Setting | `$client->settings()` |
| DeliveryCondition | `$client->deliveryConditions()` |
| PayCondition | `$client->payConditions()` |
| Supplier | `$client->suppliers()` |
| Tag | `$client->tags()` |
| Todo | `$client->todos()` |
| User | `$client->users()` |

## Contributing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse --no-progress
vendor/bin/ecs check src tests
```

PRs are welcome. The CI workflow enforces all three checks.

## License

MIT — see [LICENSE](./LICENSE).
