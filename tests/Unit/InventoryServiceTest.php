<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\FakeInventoryRepository;
use App\Service\InventoryService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class InventoryServiceTest extends TestCase
{
    public function test_receipt_updates_stock_and_ledger_atomically(): void
    {
        $repo = new FakeInventoryRepository();
        $repo->stocks['2:1'] = 4;

        (new InventoryService($repo))->move(2, 1, 'Receipt', 3, 'PO', 9, 1);

        self::assertSame(7, $repo->stocks['2:1']);
        self::assertCount(1, $repo->ledger);
        self::assertSame(3, $repo->ledger[0]['quantity']);
    }

    public function test_issue_reduces_stock_and_records_ledger(): void
    {
        $repo = new FakeInventoryRepository();
        $repo->stocks['2:1'] = 8;

        (new InventoryService($repo))->move(2, 1, 'Issue', 5, 'SO', 9, 4);

        self::assertSame(3, $repo->stocks['2:1']);
        self::assertSame('Issue', $repo->ledger[0]['movement_type']);
    }

    public function test_insufficient_stock_rolls_back_without_ledger(): void
    {
        $repo = new FakeInventoryRepository();
        $repo->stocks['2:1'] = 2;

        try {
            (new InventoryService($repo))->move(2, 1, 'Issue', 3, 'SO', 9, 4);
            self::fail('Expected insufficient stock to be rejected.');
        } catch (DomainException) {
            self::assertSame(2, $repo->stocks['2:1']);
            self::assertSame([], $repo->ledger);
            self::assertFalse($repo->inTransaction());
        }
    }

    public function test_non_positive_quantity_is_rejected(): void
    {
        $repo = new FakeInventoryRepository();
        $this->expectException(DomainException::class);
        (new InventoryService($repo))->move(2, 1, 'Receipt', 0, 'PO', 9, 1);
    }
}
