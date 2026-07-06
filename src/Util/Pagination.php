<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Util;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use Generator;

/**
 * Walks every page of a list endpoint and yields each item as it arrives.
 *
 * Accepts any callable that returns a {@see ListResponse} for a given
 * `(limit, offset)` pair. Iteration stops when:
 *  - the page returns fewer items than the requested page size (the server
 *    has no more rows), or
 *  - the running offset reaches the reported `total`.
 */
final class Pagination
{
    /**
     * @param callable(int, int): ListResponse $listCall
     *
     * @return Generator<int, array<string, mixed>, void, void>
     */
    public static function walk(callable $listCall, int $pageSize): Generator
    {
        $offset = 0;
        while (true) {
            $page = $listCall($pageSize, $offset);
            $items = $page->items;
            $count = count($items);
            if ($count === 0) {
                return;
            }

            foreach ($items as $item) {
                yield $item;
            }

            $offset += $count;

            if ($count < $pageSize || $offset >= $page->total) {
                return;
            }
        }
    }
}
