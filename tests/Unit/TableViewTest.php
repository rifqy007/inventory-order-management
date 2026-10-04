<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TableView;
use PHPUnit\Framework\TestCase;

final class TableViewTest extends TestCase
{
    public function test_active_sort_header_toggles_direction_and_marks_ascending(): void
    {
        $header = TableView::sortHeader(
            ['sort' => 'name', 'direction' => 'asc', 'page' => 3, 'q' => 'chair'],
            'name',
            'Nama',
            '/products'
        );

        self::assertStringContainsString('aria-sort="ascending"', $header);
        self::assertStringContainsString('direction=desc', $header);
        self::assertStringContainsString('page=1', $header);
        self::assertStringContainsString('q=chair', $header);
        self::assertStringContainsString('↑', $header);
    }

    public function test_inactive_sort_header_uses_neutral_aria_state(): void
    {
        $header = TableView::sortHeader(
            ['sort' => 'name', 'direction' => 'desc', 'page' => 1],
            'sku',
            'SKU',
            '/products'
        );

        self::assertStringContainsString('aria-sort="none"', $header);
        self::assertStringContainsString('aria-hidden="true">↕', $header);
    }

    public function test_sales_sort_header_does_not_expose_owner_filter(): void
    {
        $header = TableView::sortHeader(
            ['sort' => 'status', 'direction' => 'desc', 'page' => 2, 'owner' => 17],
            'status',
            'Status',
            '/sales-orders/workflow',
            true
        );

        self::assertStringContainsString('aria-sort="descending"', $header);
        self::assertStringNotContainsString('owner=17', $header);
        self::assertStringContainsString('direction=asc', $header);
    }
}
