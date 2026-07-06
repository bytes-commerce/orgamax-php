<?php

declare(strict_types=1);

/**
 * 04 - Articles and Suppliers
 *
 * Articles (the product catalogue) and suppliers (vendors) follow the
 * same create / update / upsert pattern. Demonstrates the `upsert()`
 * convenience that picks POST vs PUT based on the presence of an id.
 *
 * Run:  php examples/04-articles-suppliers.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\OrgaMaxClient;

$client = OrgaMaxClient::create(
    apiKey: getenv('ORGAMAX_API_KEY') ?: 'demo-key',
    apiSecret: getenv('ORGAMAX_API_SECRET') ?: 'demo-secret',
    ownershipId: getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership',
);

// --- Create a new article ---------------------------------------------------
$article = $client->articles()->create([
    'article' => [
        'title'      => 'Premium Widget',
        'number'     => 'WID-0001',
        'unit'       => 'Stk.',
        'price'      => 19.95,
        'vatPercent' => 19,
    ],
]);
$articleId = $article->first()['id'] ?? null;
echo "Created article id={$articleId}\n";

// --- Upsert: create-or-update the same article -----------------------------
// Pass an id to overwrite, omit it to create. Same call, two outcomes.
$client->articles()->upsert(
    payload: [
        'article' => [
            'title' => 'Premium Widget (rev)',
            'price' => 21.50,
        ],
    ],
    id: $articleId,
);
echo "Updated article via upsert()\n";

// --- Create a supplier -----------------------------------------------------
$supplier = $client->suppliers()->create([
    'supplier' => [
        'name'           => 'ACME Wholesale',
        'supplierNumber' => 'S-2001',
        'email'          => 'orders@acme-wholesale.example',
    ],
]);
$supplierId = $supplier->first()['id'] ?? null;
echo "Created supplier id={$supplierId}\n";

// --- Push a per-article setting (e.g. units, categories) -------------------
$client->articles()->createSetting([
    'units'      => 'Stk., Liter, Karton',
    'categories' => 'Zubehoer, Ersatzteile',
]);
echo "Article settings pushed\n";

// --- Page through the catalogue --------------------------------------------
$page = $client->articles()->list(limit: 100, offset: 0);
echo sprintf("Catalogue: %d of %d articles on this page\n", count($page->items), $page->total);
