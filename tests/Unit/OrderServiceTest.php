<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repository\OrderRepositoryInterface;
use App\Service\OrderService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class OrderServiceTest extends TestCase
{
    public function test_sales_can_create_a_valid_draft_order(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects(self::once())->method('begin');
        $repository->expects(self::once())->method('createSalesOrder')->willReturn(41);
        $repository->expects(self::once())->method('commit');

        $id = (new OrderService($repository))->createSalesOrder(
            ['order_number' => 'SO-test', 'customer_id' => 1, 'warehouse_id' => 1],
            [['product_id' => 3, 'quantity' => 2, 'price' => 15000]],
            2,
            'Sales'
        );

        self::assertSame(41, $id);
    }

    public function test_sales_order_requires_at_least_one_item(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $this->expectException(DomainException::class);
        (new OrderService($repository))->createSalesOrder(
            ['customer_id' => 1, 'warehouse_id' => 1], [], 2, 'Sales'
        );
    }

    public function test_duplicate_product_items_are_rejected(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $this->expectException(DomainException::class);
        (new OrderService($repository))->createPurchaseOrder(
            ['supplier_id' => 1, 'warehouse_id' => 1],
            [
                ['product_id' => 3, 'quantity' => 1, 'price' => 10],
                ['product_id' => 3, 'quantity' => 2, 'price' => 10],
            ],
            4,
            'WarehouseStaff'
        );
    }

    public function test_sales_cannot_create_purchase_orders(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $this->expectException(DomainException::class);
        (new OrderService($repository))->createPurchaseOrder(
            ['supplier_id' => 1, 'warehouse_id' => 1],
            [['product_id' => 3, 'quantity' => 1, 'price' => 10]],
            2,
            'Sales'
        );
    }

    public function test_sales_cannot_approve_sales_orders(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects(self::never())->method('salesOrder');
        $this->expectException(DomainException::class);
        (new OrderService($repository))->approveSalesOrder(10, 2, 'Sales');
    }

    public function test_order_creator_cannot_approve_own_order(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->expects(self::once())->method('salesOrder')->willReturn([
            'id' => 10,
            'created_by' => 2,
            'status' => 'PendingApproval',
        ]);
        $repository->expects(self::never())->method('changeSalesOrderStatus');
        $this->expectException(DomainException::class);
        (new OrderService($repository))->approveSalesOrder(10, 2, 'Admin');
    }
}
