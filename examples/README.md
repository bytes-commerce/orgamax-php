# Examples

Runnable PHP scripts that demonstrate real patterns against the
[OrgaMax Cloud OpenAPI](https://api.orgamax.de/openapi/documentation/).

Each example is a single, self-contained file. They assume a working
[PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client is installed
(Guzzle 7, Symfony HttpClient, ...) plus the usual PSR-17 factories —
or `php-http/discovery` will pick them up automatically.

## Setup

```bash
# from the project root
export ORGAMAX_API_KEY="..."
export ORGAMAX_API_SECRET="..."
export ORGAMAX_OWNERSHIP_ID="..."
```

## Scripts

| # | File | What it shows |
|---|------|---------------|
| 01 | `01-quickstart.php` | Three ways to construct a client; one list call |
| 02 | `02-customers.php` | Create, read, update, upsert a customer |
| 03 | `03-orders-invoices.php` | Create an order, draft an invoice from it, lock, send, record payment |
| 04 | `04-articles-suppliers.php` | Create and upsert articles and suppliers |
| 05 | `05-error-handling.php` | The exception hierarchy and how to catch each branch |
| 06 | `06-pagination.php` | Walk every page of a list endpoint with `$client->paginate()` |
| 07 | `07-retry.php` | Wrap a flaky call in `RetryHandler` for backoff / rate-limit handling |
| 08 | `08-custom-cache.php` | Plug a Redis PSR-16 cache so tokens survive across requests |

## Running

```bash
php examples/01-quickstart.php
```

The scripts are intentionally short and free of side effects. They print
the request they would send (or the response they would parse) — the
OrgaMax Cloud API itself is never hit, so the scripts are safe to run
in any environment.
