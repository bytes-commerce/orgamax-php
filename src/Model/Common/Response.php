<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Model\Common;

/**
 * Generic OrgaMax envelope `{ "data": ..., "meta": {...} }`.
 *
 * Most endpoints return this shape. `data` is intentionally untyped because
 * each resource surfaces a different record (article, customer, invoice, ...).
 * Use {@see self::first()} when the API returns a single-item list, or access
 * `data` directly when it is an associative record.
 */
final readonly class Response
{
    /**
     * @param array<int|string, mixed>|list<mixed> $data
     * @param array<string, mixed>                 $meta
     */
    public function __construct(
        public array $data,
        public array $meta,
    ) {
    }

    /**
     * @param array<int|string, mixed>|list<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $rawData = $payload['data'] ?? [];
        $data = is_array($rawData) ? $rawData : [];

        $rawMeta = $payload['meta'] ?? [];
        /** @var array<string, mixed> $meta */
        $meta = is_array($rawMeta) ? self::stringifyKeysRecursive($rawMeta) : [];

        return new self($data, $meta);
    }

    /**
     * @return array<int|string, mixed>|list<mixed>
     */
    public function first(): array
    {
        $first = $this->data[0] ?? [];

        return is_array($first) ? $first : [];
    }

    /**
     * @param array<int|string, mixed> $value
     *
     * @return array<string, mixed>
     */
    private static function stringifyKeysRecursive(array $value): array
    {
        $out = [];
        foreach ($value as $key => $v) {
            $stringKey = is_string($key) ? $key : (string) $key;
            $out[$stringKey] = is_array($v) ? self::stringifyKeysRecursive($v) : $v;
        }

        return $out;
    }
}
