---
name: orgamax-php design
description: Design + endpoint-enumeration plan for the bytes-commerce/orgamax-php Composer library — a full PHP 8.3 client for the OrgaMax Cloud OpenAPI.
type: design
---

# bytes-commerce/orgamax-php — Design Spec

Status: **approved** (verbal approval during brainstorming session 2026-07-06).
Owner: bytes-commerce.de (Maximilian Graf Schimmelmann).
Source of truth for the API surface: https://api.orgamax.de/openapi/documentation/ (OpenAPI 3.0.2).

## 1. Package identity

| Field            | Value                                 |
|------------------|---------------------------------------|
| Composer name    | `bytes-commerce/orgamax-php`          |
| PHP              | `^8.3`                                |
| Type             | `library`                             |
| License          | `MIT`                                 |
| Namespace root   | `BytesCommerce\Orgamax`               |
| Top-level facade | `BytesCommerce\Orgamax\OrgaMaxClient` |
| Server URL       | `https://api.orgamax.de/openapi`      |

## 2. Goals / non-goals

**Goals**

- Cover every operation published in the OrgaMax Cloud OpenAPI spec.
- Provide a typed, framework-agnostic PHP client usable from any 8.3+ project.
- Document the auth (ownership-id → bearer token) flow inline in the README.
- Ship with PHPUnit tests, PHPStan at MAX, ECS, Rector — all green in CI.

**Non-goals**

- No Symfony Bundle (would be a separate wrapper package).
- No internal storage of credentials.
- No retry/back-off beyond sane HTTP defaults — users wire their own middleware.

## 3. Architecture

```
BytesCommerceOrgamax \
└── OrgaMaxClient                                  # facade: constructs services + owns HTTP transport
      ├── RequestBuilder                           # builds PSR-7 requests with auth headers
      ├── ResponseHandler                          # decodes JSON, throws typed exceptions
      └── OrgaMaxTokenProvider (interface)
            ├── CachedTokenProvider (PSR-16 backed)
            └── StaticTokenProvider (no cache; user-managed)
      └── OrgaMaxAuthenticator                     # BasicAuth → ownershipId → bearer
      └── Resource\*  (one per resource group)
      ├── Model\*       (response DTOs)
      ├── Exception\*   (typed HTTP errors)
      └── Transport\*   (request/response plumbing)
```

### 3.1 Resource class names

Singular nouns under `BytesCommerce\Orgamax\Resource\` — accessor methods on the client are plural.

```
Resource\Article,        Resource\Invoice,        Resource\Order,
Resource\Customer,       Resource\Offer,          Resource\DeliveryNote,
Resource\Expense,        Resource\File,           Resource\Bookkeeping,
Resource\Supplier,       Resource\Todo,           Resource\User,
Resource\Tag,            Resource\DeliveryCondition,
Resource\PayCondition,   Resource\Setting
```

### 3.2 Auth flow

1. User creates the client with `apiKey`, `apiSecret`, `ownershipId` + a PSR-18 client + a PSR-16 cache.
2. First call to any resource method:
    - `OrgaMaxAuthenticator` checks `CachedTokenProvider` for a non-expired token under key
      `orgamax.token.{ownershipId}`.
    - If absent, exchange `ownershipId` (BasicAuth with apiKey:apiSecret) at `POST /auth/token`.
    - Cache the JWT (24h TTL) in the PSR-16 cache.
3. Every subsequent request adds `Authorization: Bearer {token}`.
4. On 401, the cache entry is purged and a single retry is performed.

### 3.3 Error model

Non-2xx responses throw typed exceptions, all extending `OrgaMaxException`:

| Status | Exception                 | Carries                               |
|--------|---------------------------|---------------------------------------|
| 400    | `ValidationException`     | response body                         |
| 401    | `AuthenticationException` | response body                         |
| 404    | `NotFoundException`       | response body                         |
| 422    | `ValidationException`     | response body (ItemValidationError[]) |
| 429    | `RateLimitException`      | `Retry-After` header                  |
| 5xx    | `ServerException`         | response body                         |

## 4. Dependency surface

```jsonc
{
  "require": {
    "php": "^8.3",
    "ext-json": "*",
    "psr/http-client": "^1.0",
    "psr/http-factory": "^1.0",
    "psr/http-message": "^1.1 || ^2.0",
    "psr/simple-cache": "^3.0",
    "php-http/discovery": "^1.19"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "phpstan/phpstan": "^1.12",
    "phpstan/phpstan-deprecation-rules": "^1.2",
    "phpstan/phpstan-strict-rules": "^1.6",
    "ergebnis/phpstan-rules": "^2.0",
    "phpstan/extension-installer": "^1.4",
    "phpstan/phpstan-webmozart-assert": "^1.2",
    "webmozart/assert": "^1.11",
    "rector/rector": "^1.2",
    "symplify/easy-coding-standard": "^12.3"
  }
}
```

## 5. Endpoint coverage — REQUIRED EVERY endpoint

For the implementation plan below, every row must end up with a Unit test + a Resource method.

| #  | Method | Path                              | Resource          | Operation                  | Test |
|----|--------|-----------------------------------|-------------------|----------------------------|------|
| 1  | POST   | `/article/`                       | Article           | createArticle              | ✓    |
| 2  | GET    | `/article/`                       | Article           | getArticles                | ✓    |
| 3  | DELETE | `/article/{id}`                   | Article           | deleteArticle              | ✓    |
| 4  | PUT    | `/article/{id}`                   | Article           | putArticle                 | ✓    |
| 5  | GET    | `/article/{id}`                   | Article           | getArticle                 | ✓    |
| 6  | POST   | `/auth/token`                     | Authenticator     | getToken                   | ✓    |
| 7  | GET    | `/bookkeeping/getchartofaccounts` | Bookkeeping       | bookkeeping                | ✓    |
| 8  | POST   | `/customer/`                      | Customer          | createCustomer             | ✓    |
| 9  | PUT    | `/customer/{id}`                  | Customer          | putCustomer                | ✓    |
| 10 | GET    | `/customer/{id}`                  | Customer          | getCustomer                | ✓    |
| 11 | GET    | `/customer`                       | Customer          | getCustomers               | ✓    |
| 12 | GET    | `/deliveryNote/document/{id}`     | DeliveryNote      | getdeliveryNoteDocument    | ✓    |
| 13 | GET    | `/deliveryNote/{id}`              | DeliveryNote      | getdeliveryNote            | ✓    |
| 14 | GET    | `/deliveryNote`                   | DeliveryNote      | getDeliveryNotes           | ✓    |
| 15 | POST   | `/expense/`                       | Expense           | createExpense              | ✓    |
| 16 | DELETE | `/expense/{id}`                   | Expense           | deleteExpense              | ✓    |
| 17 | GET    | `/expense/{id}`                   | Expense           | getExpense                 | ✓    |
| 18 | PUT    | `/expense/{id}`                   | Expense           | updateExpense              | ✓    |
| 19 | DELETE | `/expense/receipt/{id}`           | Expense           | deleteExpenseReceipt       | ✓    |
| 20 | GET    | `/expense`                        | Expense           | getExpenses                | ✓    |
| 21 | GET    | `/file/{id}/analyze`              | File              | analyzeFile                | ✓    |
| 22 | DELETE | `/file/{id}`                      | File              | deleteFile                 | ✓    |
| 23 | GET    | `/file/{id}`                      | File              | getDocument                | ✓    |
| 24 | PUT    | `/file/{id}/meta`                 | File              | editFile                   | ✓    |
| 25 | GET    | `/file/{id}/meta`                 | File              | fetchFileInfo              | ✓    |
| 26 | GET    | `/file`                           | File              | getFiles                   | ✓    |
| 27 | POST   | `/file/upload`                    | File              | uploadFile                 | ✓    |
| 28 | POST   | `/invoice/{id}/payment`           | Invoice           | addPayment                 | ✓    |
| 29 | GET    | `/invoice/document/{id}`          | Invoice           | getinvoiceDocument         | ✓    |
| 30 | GET    | `/invoice/{id}/download`          | Invoice           | download File (deprecated) | ✓    |
| 31 | GET    | `/invoice/{id}`                   | Invoice           | getInvoice                 | ✓    |
| 32 | GET    | `/invoice`                        | Invoice           | getInvoices                | ✓    |
| 33 | PUT    | `/invoice/{id}/lock`              | Invoice           | lockInvoice                | ✓    |
| 34 | POST   | `/invoice/{id}/send`              | Invoice           | sendInvoice                | ✓    |
| 35 | GET    | `/offer/document/{id}`            | Offer             | getofferDocument           | ✓    |
| 36 | GET    | `/offer/{id}`                     | Offer             | getOffer                   | ✓    |
| 37 | GET    | `/offer`                          | Offer             | getOffers                  | ✓    |
| 38 | POST   | `/order/{id}/invoice`             | Order             | postorderInvoice           | ✓    |
| 39 | POST   | `/order/`                         | Order             | postorder                  | ✓    |
| 40 | GET    | `/order/document/{id}`            | Order             | getOrderDocument           | ✓    |
| 41 | GET    | `/order/{id}`                     | Order             | getOrder                   | ✓    |
| 42 | GET    | `/order`                          | Order             | getOrders                  | ✓    |
| 43 | GET    | `/setting/account`                | Setting           | getAccountSetting          | ✓    |
| 44 | POST   | `/setting/article`                | Article           | createArticleSetting       | ✓    |
| 45 | GET    | `/setting/deliveryCondition`      | DeliveryCondition | getDeliveryConditions      | ✓    |
| 46 | GET    | `/setting/deliveryCondition/{id}` | DeliveryCondition | getDeliveryCondition       | ✓    |
| 47 | PUT    | `/setting/deliveryCondition/{id}` | DeliveryCondition | updateDeliveryCondition    | ✓    |
| 48 | POST   | `/setting/deliveryCondition/`     | DeliveryCondition | createDeliveryCondition    | ✓    |
| 49 | GET    | `/setting/miscellaneous`          | Setting           | articleSettings            | ✓    |
| 50 | GET    | `/setting/payCondition`           | PayCondition      | getPayConditions           | ✓    |
| 51 | GET    | `/setting/payCondition/{id}`      | PayCondition      | getPayCondition            | ✓    |
| 52 | POST   | `/setting/payCondition/`          | PayCondition      | createPayCondition         | ✓    |
| 53 | PUT    | `/setting/payCondition/`          | PayCondition      | updatePayCondition         | ✓    |
| 54 | POST   | `/supplier`                       | Supplier          | postSuppliers              | ✓    |
| 55 | GET    | `/supplier`                       | Supplier          | getSuppliers               | ✓    |
| 56 | DELETE | `/supplier/{id}`                  | Supplier          | deleteSupplier             | ✓    |
| 57 | PUT    | `/supplier/{id}`                  | Supplier          | putSupplier                | ✓    |
| 58 | GET    | `/supplier/{id}`                  | Supplier          | getSupplier                | ✓    |
| 59 | GET    | `/tags`                           | Tag               | gettags                    | ✓    |
| 60 | POST   | `/todo/`                          | Todo              | createTodo                 | ✓    |
| 61 | POST   | `/todo/{id}/message`              | Todo              | createTodoMessage          | ✓    |
| 62 | DELETE | `/todo/{id}`                      | Todo              | deleteToDo                 | ✓    |
| 63 | GET    | `/todo/{id}`                      | Todo              | getTodoMessages            | ✓    |
| 64 | PUT    | `/todo/{id}`                      | Todo              | setDate                    | ✓    |
| 65 | POST   | `/todo/message/{id}`              | Todo              | deleteTodoMessage          | ✓    |
| 66 | GET    | `/todo`                           | Todo              | getTodos                   | ✓    |
| 67 | PUT    | `/todo/{id}/link`                 | Todo              | linkToTodo                 | ✓    |
| 68 | PUT    | `/todo/{id}/unlink`               | Todo              | unlinkToTodo               | ✓    |
| 69 | PUT    | `/todo/{id}/status`               | Todo              | setTodoStatus              | ✓    |
| 70 | GET    | `/user`                           | User              | getUsers                   | ✓    |

70 operations / 70 unit tests.

## 6. Testing strategy

- `FakeHttpClient` test double implements `ClientInterface` and returns canned `ResponseInterface` fixtures — no real
  HTTP during unit tests.
- `FakeTokenProvider` returns a static token; tests do not exercise the real auth flow.
- CachedTokenProvider has its own dedicated test using `FilesystemCache` (PSR-16 in-memory adapter).
- Resource tests assert: outgoing request URI, headers (`Authorization: Bearer …`, `Content-Type`), method, parsed
  query / path / body payload; the deserialized response shape.

Test layout:

```
tests/Unit/
├── Auth/
├── Transport/
├── Resource/ArticleTest.php
├── Resource/InvoiceTest.php
├── ...   (one per Resource class)
└── Model/...
```

## 7. PHPStan — MAX configuration

`phpstan.neon`:

```neon
includes:
    - vendor/phpstan/phpstan-deprecation-rules/rules.neon
    - vendor/phpstan/phpstan-strict-rules/rules.neon
    - vendor/ergebnis/phpstan-rules/config/common.neon   # if shipped as a preset
    - vendor/ergebnis/phpstan-rules/config/strict.neon

parameters:
    level: max
    paths:
        - src
        - tests

    treatPhpDocTypesAsCertain: false
    reportUnmatchedIgnoredErrors: false
    parallel:
        maximumNumberOfProcesses: 8

    strictRules:
        all: true
        booleansInConditions: true
        booleansInLoopConditions: true
        callToNonExistentMethod: true
        numericCast: true
        requireParentConstructorCall: true

    errorFormatter:
        ...
```

Additionally:

- `phpstan-baseline.neon` exists but stays empty unless strictly needed (we aim to ship with zero ignores).
- CI runs `vendor/bin/phpstan analyse --no-progress --error-format=github`.

## 8. CI / GitHub Actions

`.github/workflows/ci.yml` runs on push + PR:

1. Setup PHP matrix 8.3, 8.4
2. `composer install`
3. `vendor/bin/phpunit`
4. `vendor/bin/phpstan analyse --no-progress`
5. `vendor/bin/ecs check src tests`
6. `vendor/bin/rector --dry-run`

## 9. Repository layout (target tree)

```
.
├── .github/workflows/ci.yml
├── .gitignore
├── LICENSE
├── README.md
├── CHANGELOG.md
├── composer.json
├── phpstan.neon
├── phpunit.xml.dist
├── ecs.php
├── rector.php
├── docs/superpowers/specs/2026-07-06-orgamax-php-design.md
├── src/
└── tests/
```

## 10. Open questions — answered during brainstorming

- "Symfony plugin" — interpreted as **standalone Composer library**, no Symfony dependency. ✓
- API target — **OrgaMax Cloud REST API**, full spec at `https://api.orgamax.de/openapi/`. ✓
- HTTP stack — **PSR-18 + PSR-17 + php-http/discovery**. ✓
- Token storage — **PSR-16 SimpleCache**. ✓
- Namespace — **BytesCommerce\Orgamax**; facade class **OrgaMaxClient**. ✓
- PHP version — **^8.3**. ✓
- Strict typing — **MAX PHPStan + Ergebnis rules + deprecation + strict-rules**. ✓
