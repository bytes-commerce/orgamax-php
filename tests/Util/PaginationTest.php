<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Util;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Util\Pagination;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    public function testYieldsNothingWhenFirstPageIsEmpty(): void
    {
        $callCount = 0;
        $pages = (function () use (&$callCount): Generator {
            ++$callCount;
            yield new ListResponse(items: [], total: 0, limit: 10, offset: 0);
        })();

        $collected = [];
        foreach (Pagination::walk(static fn (int $l, int $o): ListResponse => $pages->current(), pageSize: 10) as $row) {
            $collected[] = $row;
        }

        self::assertSame([], $collected);
        self::assertSame(1, $callCount, 'must stop after the first empty page');
    }

    public function testWalksMultiplePages(): void
    {
        $page1 = new ListResponse(
            items: [[
                'id' => 1,
            ], [
                'id' => 2,
            ]],
            total: 5,
            limit: 2,
            offset: 0,
        );
        $page2 = new ListResponse(
            items: [[
                'id' => 3,
            ], [
                'id' => 4,
            ]],
            total: 5,
            limit: 2,
            offset: 2,
        );
        $page3 = new ListResponse(
            items: [[
                'id' => 5,
            ]],
            total: 5,
            limit: 2,
            offset: 4,
        );

        $queue = [$page1, $page2, $page3];
        $collected = [];
        foreach (Pagination::walk(static function (int $l, int $o) use (&$queue): ListResponse {
            return array_shift($queue) ?? new ListResponse([], 0, 0, 0);
        }, pageSize: 2) as $row) {
            $collected[] = $row['id'];
        }

        self::assertSame([1, 2, 3, 4, 5], $collected);
    }

    public function testStopsWhenPageIsShorterThanPageSize(): void
    {
        $page1 = new ListResponse(
            items: [[
                'id' => 1,
            ]],
            total: 100,
            limit: 10,
            offset: 0,
        );

        $collected = [];
        foreach (Pagination::walk(static fn (): ListResponse => $page1, pageSize: 10) as $row) {
            $collected[] = $row['id'];
        }

        self::assertSame([1], $collected);
    }
}
