<?php

declare(strict_types=1);

namespace App\Repository;

interface InventoryRepositoryInterface
{
    /** Kontrak ini memisahkan business logic dari PDO sehingga Service dapat diuji memakai Fake repository. */
    public function findUserByEmail(string $email): ?array;
    public function dashboard(string $role, int $userId): array;
    public function products(array $filter = []): array;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
    public function inTransaction(): bool;
    public function findStockForUpdate(int $productId, int $warehouseId): ?array;
    public function setStock(int $productId, int $warehouseId, int $quantity): void;
    public function addLedger(array $data): void;
}
