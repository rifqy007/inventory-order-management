<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Implementasi in-memory untuk unit test.
 *
 * Repository ini membuktikan bahwa Service tidak bergantung
 * secara langsung pada implementasi MySQL.
 */
final class FakeInventoryRepository implements InventoryRepositoryInterface
{
    public array $users = [];

    public array $stocks = [];

    public array $ledger = [];

    private ?array $transactionSnapshot = null;

    public function beginTransaction(): void
    {
        $this->transactionSnapshot = [$this->stocks, $this->ledger];
    }

    public function commit(): void
    {
        $this->transactionSnapshot = null;
    }

    public function rollBack(): void
    {
        if ($this->transactionSnapshot !== null) {
            [$this->stocks, $this->ledger] = $this->transactionSnapshot;
            $this->transactionSnapshot = null;
        }
    }

    public function inTransaction(): bool
    {
        return $this->transactionSnapshot !== null;
    }

    public function findUserByEmail(string $email): ?array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }

        return null;
    }

    public function dashboard(string $role, int $userId): array
    {
        return [];
    }

    public function products(array $filter = []): array
    {
        return [];
    }

    public function findStockForUpdate(
        int $productId,
        int $warehouseId
    ): ?array {
        $stockKey = $productId . ':' . $warehouseId;

        if (!isset($this->stocks[$stockKey])) {
            return null;
        }

        return [
            'quantity' => $this->stocks[$stockKey],
        ];
    }

    public function setStock(
        int $productId,
        int $warehouseId,
        int $quantity
    ): void {
        $stockKey = $productId . ':' . $warehouseId;

        $this->stocks[$stockKey] = $quantity;
    }

    public function addLedger(array $data): void
    {
        $this->ledger[] = $data;
    }
}
