<?php

declare(strict_types=1);

namespace App\Repository;

/** Boundary repository untuk workflow PO, SO, stok, dan ledger. */
interface OrderRepositoryInterface
{
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
    public function inTransaction(): bool;
    public function createSalesOrder(array $header, array $items): int;
    public function salesOrder(int $id): ?array;
    public function salesOrders(array $filter): array;
    public function salesOrderCount(array $filter): int;
    public function changeSalesOrderStatus(int $id, string $from, string $to, array $audit = []): bool;
    public function createPurchaseOrder(array $header, array $items): int;
    public function purchaseOrder(int $id): ?array;
    public function purchaseOrders(array $filter): array;
    public function purchaseOrderCount(array $filter): int;
    public function changePurchaseOrderStatus(int $id, array $allowedFrom, string $to): bool;
    public function lockStock(int $productId, int $warehouseId): int;
    public function saveStock(int $productId, int $warehouseId, int $quantity): void;
    public function addLedger(array $ledger): void;
    public function updateReceivedQuantity(int $itemId, int $quantity): void;
    public function ledger(array $filter): array;
    public function orderReport(array $filter): array;
}
