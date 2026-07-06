<?php

declare(strict_types=1);

/**
 * 05 - Error handling
 *
 * Every API call can throw one of the exceptions under
 * BytesCommerce\Orgamax\Exception. Catch them in order from most specific
 * to most general, since PHP will pick the first matching catch.
 *
 * Run:  php examples/05-error-handling.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\Exception\AuthenticationException;
use BytesCommerce\Orgamax\Exception\NotFoundException;
use BytesCommerce\Orgamax\Exception\OrgaMaxException;
use BytesCommerce\Orgamax\Exception\RateLimitException;
use BytesCommerce\Orgamax\Exception\ServerException;
use BytesCommerce\Orgamax\Exception\TransportException;
use BytesCommerce\Orgamax\Exception\ValidationException;
use BytesCommerce\Orgamax\OrgaMaxClient;

$client = OrgaMaxClient::create(
    apiKey: getenv('ORGAMAX_API_KEY') ?: 'demo-key',
    apiSecret: getenv('ORGAMAX_API_SECRET') ?: 'demo-secret',
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership',
);

try {
    $invoice = $client->invoices()->get(id: 'i-does-not-exist');
} catch (AuthenticationException $e) {
    // 401 — your apiKey / apiSecret pair was rejected. Rotate the
    // credentials and rebuild the client.
    echo "Auth failed: {$e->getMessage()}\n";

} catch (RateLimitException $e) {
    // 429 — back off. $e->retryAfter() honours the `Retry-After` header
    // (in seconds). $e->hasRetryAfter() returns false if the server
    // did not send one.
    $wait = $e->hasRetryAfter() ? $e->retryAfter() : 5;
    echo "Rate limited, sleeping {$wait}s then retry\n";
    sleep($wait);

} catch (NotFoundException $e) {
    // 404 — the id you asked for does not exist. Either it was deleted
    // or you have a typo.
    echo "Not found (HTTP {$e->httpStatusCode()}): {$e->getMessage()}\n";

} catch (ValidationException $e) {
    // 422 — your request body is invalid. $e->responseBody() returns the
    // parsed error payload (often a per-field error map).
    echo "Validation failed: " . json_encode($e->responseBody()) . "\n";

} catch (ServerException $e) {
    // 5xx — the OrgaMax side is having a bad day. Surface it; consider
    // a retry via RetryHandler (see 07-retry.php).
    echo "Server error (HTTP {$e->httpStatusCode()})\n";

} catch (TransportException $e) {
    // PSR-18 client threw — DNS, TLS, timeout, connection reset. Your
    // own retry layer is the right place to handle this.
    echo "Network problem: {$e->getMessage()}\n";

} catch (OrgaMaxException $e) {
    // Catch-all for any other non-2xx response. Inspect
    // httpStatusCode() / responseBody() / responseHeaders().
    echo "API error {$e->httpStatusCode()}: {$e->getMessage()}\n";
}
