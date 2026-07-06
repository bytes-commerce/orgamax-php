<?php

declare(strict_types=1);

/**
 * 06 - Pagination
 *
 * The OrgaMax OpenAPI list endpoints return a single page per call.
 * $client->paginate() walks every page for you and yields each row, so
 * you can write a simple `foreach` and stop worrying about offsets.
 *
 * Run:  php examples/06-pagination.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\OrgaMaxClient as Client;

$client = OrgaMaxClient::create(
    apiKey: getenv('ORGAMAX_API_KEY') ?: 'demo-key',
    apiSecret: getenv('ORGAMAX_API_SECRET') ?: 'demo-secret',
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership',
);

// --- Walk every customer, 100 per page -------------------------------------
$emails = [];
foreach ($client->paginate(
    fn (int $limit, int $offset): ListResponse => $client->customers()->list(limit: $limit, offset: $offset),
    pageSize: 100,
) as $customer) {
    if (isset($customer['email'])) {
        $emails[] = $customer['email'];
    }
}

echo "Pulled " . count($emails) . " customer email addresses\n";

// --- Same thing, but for orders -------------------------------------------
$orders = 0;
foreach ($client->paginate(
    fn (int $limit, int $offset): ListResponse => $client->orders()->list(limit: $limit, offset: $offset, filter: "orderDate>='2026-01-01'"),
    pageSize: 50,
) as $order) {
    ++$orders;
}

echo "Pulled {$orders} orders since 2026-01-01\n";

// --- Why this matters -----------------------------------------------------
// Without paginate() you would have to:
//   - call list() in a loop,
//   - increment offset by `count(items)`,
//   - know to stop when items is shorter than limit OR offset >= total.
// paginate() is a Generator so it is lazy: no full result-set is held
// in memory, even when the catalogue has 50,000 entries.
