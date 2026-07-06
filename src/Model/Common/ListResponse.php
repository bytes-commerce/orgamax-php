<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Model\Common;

/**
 * Generic list envelope `{ "data": [...], "meta": { "total", "limit", "offset" } }`.
 *
 * Used by every list endpoint (Article, Customer, Invoice, ...). Each list
 * endpoint exposes `total`, `limit`, `offset`; the records inside `data`
 * stay untyped.
 */
final readonly class ListResponse
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }

    /**
     * @param array<int|string, mixed>|list<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $items = [];
        $rawData = $payload['data'] ?? [];
        if (is_array($rawData)) {
            /** @var mixed $entry */
            foreach ($rawData as $entry) {
                if (is_array($entry)) {
                    /** @var array<int|string, mixed> $entry */
                    $items[] = self::stringifyKeysRecursive($entry);
                }
            }
        }

        $rawMeta = $payload['meta'] ?? [];
        /** @var array<string, mixed> $meta */
        $meta = is_array($rawMeta) ? self::stringifyKeysRecursive($rawMeta) : [];

        return new self(
            items: $items,
            total: self::asInt($meta['total'] ?? 0),
            limit: self::asInt($meta['limit'] ?? 0),
            offset: self::asInt($meta['offset'] ?? 0),
        );
    }

    private static function asInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && $value !== '' && is_numeric($value)) {
            return (int) $value;
        }

        return 0;
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
