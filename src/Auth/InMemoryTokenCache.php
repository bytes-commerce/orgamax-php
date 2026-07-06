<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Auth;

use DateInterval;
use Override;
use Psr\SimpleCache\CacheInterface;

/**
 * Process-local in-memory PSR-16 cache — used as a default when the user does
 * not inject their own cache implementation. Tokens survive across requests
 * inside the same PHP process (e.g. one HTTP request handled by a long-running
 * worker) but not across separate process executions.
 *
 * @internal The nullable/default signatures on `get`/`getMultiple`/`set`/`setMultiple`
 *           are dictated by the PSR-16 contract (PSR-16 v3 §1.1 CacheInterface).
 *           They are flagged by the strict ergebnis rules but cannot be changed
 *           without violating the upstream interface specification.
 */
final class InMemoryTokenCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }

    #[Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    #[Override]
    public function delete(string $key): bool
    {
        if (! array_key_exists($key, $this->values)) {
            return false;
        }

        unset($this->values[$key]);

        return true;
    }

    #[Override]
    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    /**
     * @param iterable<string> $keys
     *
     * @return iterable<string, mixed>
     */
    #[Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = array_key_exists($key, $this->values) ? $this->values[$key] : $default;
        }

        return $out;
    }

    /**
     * @phpstan-ignore-next-line Contravariant — see PSR-16 signature. PHPDoc
     *                                    tightening is intentional.
     *
     * @param iterable<int|string, mixed> $values
     */
    #[Override]
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        /** @var mixed $value */
        foreach ($values as $key => $value) {
            $stringKey = is_string($key) ? $key : (string) $key;
            $this->values[$stringKey] = $value;
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    #[Override]
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->values[$key]);
        }

        return true;
    }

    #[Override]
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
