<?php

declare(strict_types=1);

/**
 * 08 - Custom token cache (Redis, filesystem, ...)
 *
 * By default the client keeps the bearer token in an in-memory cache
 * (InMemoryTokenCache), which dies with the request. To share the token
 * across requests / workers / processes, swap in any PSR-16 cache
 * implementation via the builder.
 *
 * This example uses Symfony's RedisAdapter. Any other PSR-16 cache
 * (Filesystem, Memcached, Apcu, ...) works the same way.
 *
 * Run:  composer require symfony/cache predis/predis
 *        php examples/08-custom-cache.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use BytesCommerce\Orgamax\OrgaMaxClient;
use Symfony\Component\Cache\Adapter\RedisAdapter;

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$cache = new RedisAdapter($redis, namespace: 'orgamax', defaultLifetime: 86_400);

$client = OrgaMaxClient::builder()
    ->withApiKey(getenv('ORGAMAX_API_KEY') ?: 'demo-key')
    ->withApiSecret(getenv('ORGAMAX_API_SECRET') ?: 'demo-secret')
    ->withOwnershipId(getenv('ORGAMAX_OWNERSHIP_ID') ?: 'demo-ownership')
    ->withTokenCache($cache)
    ->build();

// The bearer token (TTL ~24h) is now stored under
// `orgamax.token.{ownershipId}` in Redis and shared across every
// process / request that points at the same Redis instance.
$articles = $client->articles()->list(limit: 10, offset: 0);
echo "Fetched " . count($articles->items) . " articles using a cached token\n";

// --- Pure PSR-16 alternative: filesystem cache ---------------------------
// $cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
//     namespace: 'orgamax',
//     defaultLifetime: 86_400,
// );

// --- Pure PSR-16 alternative: APCu (single-host, no Redis) ---------------
// $cache = new \Symfony\Component\Cache\Adapter\ApcuAdapter(
//     namespace: 'orgamax',
//     defaultLifetime: 86_400,
// );
