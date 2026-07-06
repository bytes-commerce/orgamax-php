<?php

declare(strict_types=1);

/**
 * 02 - Customers: CRUD + upsert
 *
 * Demonstrates the full lifecycle on the Customer resource. The shape of
 * the request body mirrors the OrgaMax OpenAPI spec for `/customer/`.
 *
 * Run:  php examples/02-customers.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\OrgaMaxClient;

$client = OrgaMaxClient::create(
    apiKey: getenv('ORGAMAX_API_KEY') ?: 'demo-key',
    apiSecret: getenv('ORGAMAX_API_SECRET') ?: 'demo-secret',
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership',
);

// --- 1. Create -------------------------------------------------------------
$created = $client->customers()->create([
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

$customerId = $created->first()['id'] ?? null;
echo "Created customer id={$customerId}\n";

// --- 2. Read ---------------------------------------------------------------
$customer = $client->customers()->get($customerId);
echo "Fetched: " . $customer->first()['name'] . "\n";

// --- 3. Update (partial) ---------------------------------------------------
$client->customers()->update($customerId, [
    'customer' => ['email' => 'neu@acme.example'],
]);
echo "Updated email address\n";

// --- 4. Upsert: pass an id to update, omit it to create -------------------
$client->customers()->upsert(
    payload: ['customer' => ['name' => 'ACME GmbH']],
    id: $customerId, // <-- omit to create instead
);
echo "Upsert (update) succeeded\n";

// --- 5. List a page --------------------------------------------------------
$page = $client->customers()->list(limit: 25, offset: 0);
echo sprintf(
    "Page 1: %d of %d customers\n",
    count($page->items),
    $page->total,
);
