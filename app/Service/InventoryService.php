<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\InventoryRepositoryInterface;
use DomainException;
use Throwable;

final class InventoryService
{
    public function __construct(private InventoryRepositoryInterface $repo)
    {
    }
    /**
     * Satu transaksi mengunci baris stok, menghitung stok baru, menulis ProductStock
     * dan StockLedger. FOR UPDATE mencegah dua request membaca stok lama yang sama.
     */
    public function move(int $productId, int $warehouseId, string $type, int $qty, string $refType, int $refId, int $userId): void
    {
        if ($qty <= 0) {
            throw new DomainException('Jumlah harus lebih dari nol.');
        }
        if (!in_array($type, ['Receipt', 'Issue', 'Adjustment'], true)) {
            throw new DomainException('Jenis pergerakan stok tidak valid.');
        }
        $this->repo->beginTransaction();
        try {
            $row = $this->repo->findStockForUpdate($productId, $warehouseId);
            $before = (int)($row['quantity'] ?? 0);
            $after = $type === 'Issue' ? $before - $qty : $before + $qty;
            if ($after < 0) {
                throw new DomainException("Stok tidak cukup. Stok tersedia: $before.");
            }
            $this->repo->setStock($productId, $warehouseId, $after);
            $this->repo->addLedger([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'movement_type' => $type,
                'quantity' => $qty,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'performed_by' => $userId,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'notes' => 'Pergerakan stok manual',
            ]);
            $this->repo->commit();
        } catch (Throwable $e) {
            if ($this->repo->inTransaction()) {
                $this->repo->rollBack();
            }
            throw $e;
        }
    }
}
