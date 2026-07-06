<?php

declare(strict_types=1);

/**
 * 07 - Retry with RetryHandler
 *
 * Transient failures (429 rate limit, 5xx server, network blip) deserve
 * a retry. The bundled RetryHandler re-runs a closure with exponential
 * backoff, honouring the server's `Retry-After` header.
 *
 * Run:  php examples/07-retry.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\Util\RetryHandler;

$client = OrgaMaxClient::create(
    apiKey: getenv('ORGAMAX_API_KEY') ?: 'demo-key',
    apiSecret: getenv('ORGAMAX_API_SECRET') ?: 'demo-secret',
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership',
);

// --- Default policy: 3 attempts, 200ms base, capped at 5s ----------------
$retry = RetryHandler::default();

$invoice = $retry->run(static fn () => $client->invoices()->get(id: 'i-12345'));
echo "Got invoice after up to 3 attempts: " . $invoice->first()['number'] . "\n";

// --- Aggressive policy: 5 attempts, short backoff, capped at 1s ----------
$fastRetry = new RetryHandler(
    maxAttempts: 5,
    baseDelayMs: 50,
    maxDelayMs: 1_000,
);

$page = $fastRetry->run(static fn () => $client->customers()->list(limit: 25, offset: 0));
echo "Listed customers after up to 5 attempts: " . count($page->items) . " rows\n";

// --- Compute a backoff manually (e.g. for a custom scheduler) -----------
echo "Backoff schedule for 3 attempts (200ms base, 5s cap):\n";
foreach (range(1, 3) as $attempt) {
    echo sprintf("  attempt %d -> %d ms\n", $attempt, $fastRetry->computeDelay($attempt));
}

// --- What does and does NOT get retried ----------------------------------
//   retried:  RateLimitException     (honours Retry-After)
//             TransportException     (network blip)
//             ServerException        (5xx)
//             AuthenticationException (after invalidating the cached token)
//   not retried: ValidationException, NotFoundException, generic
//                OrgaMaxException - 4xx errors that a retry cannot fix.
