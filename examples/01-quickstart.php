<?php

declare(strict_types=1);

/**
 * 01 - Quickstart
 *
 * Three factory styles for the same client. Pick whichever fits your code.
 * All three produce an identical OrgaMaxClient instance.
 *
 * Run:  php examples/01-quickstart.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\OrgaMaxClient;
use BytesCommerce\Orgamax\OrgaMaxConfig;

$apiKey = getenv('ORGAMAX_API_KEY') ?: 'demo-key';
$apiSecret = getenv('ORGAMAX_API_SECRET') ?: 'demo-secret';
$ownershipId = getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership';

// --- 1. Fluent builder (recommended for app code) ---------------------
$client = OrgaMaxClient::builder()
    ->withApiKey($apiKey)
    ->withApiSecret($apiSecret)
    ->withOwnershipId($ownershipId)
    ->build();

// --- 2. Named-arg factory (one-liner, scripts) ------------------------
// $client = OrgaMaxClient::create(
//     apiKey: $apiKey,
//     apiSecret: $apiSecret,
//     ownershipId: $ownershipId,
// );

// --- 3. Immutable config (DI containers) ------------------------------
// $config = OrgaMaxConfig::default($apiKey, $apiSecret, $ownershipId);
// $client = OrgaMaxClient::fromConfig($config);

echo "Client ready. Resources you can reach:\n";
foreach (['articles', 'customers', 'orders', 'invoices', 'suppliers'] as $name) {
    $resource = $client->{$name}();
    echo sprintf("  - \$client->%s() : %s\n", $name, $resource::class);
}
