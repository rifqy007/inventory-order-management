<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SalesOrder;
use App\Repository\OrderRepositoryInterface;
use DomainException;
use Throwable;

/** Business rules seluruh transisi PO/SO dan perubahan stok. */
final class OrderService
{
    public function __construct(private OrderRepositoryInterface $orders)
    {
    }
    public function createSalesOrder(array $header, array $items, int $userId, string $role): int
    {
        if (!in_array($role, ['Admin', 'Sales'], true)) {
            throw new DomainException('Anda tidak berwenang membuat Sales Order.');
        }
        $items = $this->validatedItems($items);
        if ((int) ($header['customer_id'] ?? 0) < 1 || (int) ($header['warehouse_id'] ?? 0) < 1) {
            throw new DomainException('Customer dan gudang wajib dipilih.');
        }
        $header['created_by'] = $userId;
        $this->orders->begin();
        try {
            $id = $this->orders->createSalesOrder($header, $items);
            $this->orders->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->orders->inTransaction()) {
                $this->orders->rollback();
            }
            throw $e;
        }
    }

    public function createPurchaseOrder(array $header, array $items, int $userId, string $role): int
    {
        if (!in_array($role, ['Admin', 'WarehouseStaff'], true)) {
            throw new DomainException('Anda tidak berwenang membuat Purchase Order.');
        }
        $items = $this->validatedItems($items);
        if ((int) ($header['supplier_id'] ?? 0) < 1 || (int) ($header['warehouse_id'] ?? 0) < 1) {
            throw new DomainException('Supplier dan gudang wajib dipilih.');
        }
        $header['created_by'] = $userId;
        $this->orders->begin();
        try {
            $id = $this->orders->createPurchaseOrder($header, $items);
            $this->orders->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->orders->inTransaction()) {
                $this->orders->rollback();
            }
            throw $e;
        }
    }

    private function validatedItems(array $items): array
    {
        if ($items === []) {
            throw new DomainException('Order minimal memiliki satu item.');
        }
        $validated = [];
        $seen = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = filter_var($item['price'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($productId < 1 || $quantity < 1 || $price === false || $price < 0) {
                throw new DomainException('Produk, jumlah, atau harga item tidak valid.');
            }
            if (isset($seen[$productId])) {
                throw new DomainException('Produk yang sama tidak boleh muncul lebih dari sekali.');
            }
            $seen[$productId] = true;
            $validated[] = ['product_id' => $productId, 'quantity' => $quantity, 'price' => (float) $price];
        }
        return $validated;
    }
    public function submitSalesOrder(int $id, int $userId, string $role): void
    {
        $o = $this->requiredSo($id);
        SalesOrder::fromRecord($o)->transitionTo('PendingApproval');
        if ($role === 'Sales' && (int)$o['created_by'] !== $userId) {
            throw new DomainException('Sales hanya dapat mengajukan order miliknya sendiri.');
        }
        if (!$this->orders->changeSalesOrderStatus($id, 'Draft', 'PendingApproval', ['submitted' => true])) {
            throw new DomainException('Order hanya dapat diajukan dari status Draft.');
        }
    }
    public function approveSalesOrder(int $id, int $userId, string $role): void
    {
        if ($role !== 'Admin') {
            throw new DomainException('Hanya Admin yang dapat menyetujui Sales Order.');
        }$o = $this->requiredSo($id);
        SalesOrder::fromRecord($o)->transitionTo('Approved');
        if ((int)$o['created_by'] === $userId) {
            throw new DomainException('Pembuat order tidak boleh menyetujui order yang sama.');
        }
        if (!$this->orders->changeSalesOrderStatus($id, 'PendingApproval', 'Approved', ['approved_by' => $userId])) {
            throw new DomainException('Order bukan dalam status Menunggu Persetujuan.');
        }
    }
    public function rejectSalesOrder(int $id, int $userId, string $role, string $reason): void
    {
        if ($role !== 'Admin') {
            throw new DomainException('Hanya Admin yang dapat menolak Sales Order.');
        }
        if (trim($reason) === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }$o = $this->requiredSo($id);
        SalesOrder::fromRecord($o)->transitionTo('Cancelled');
        if ((int)$o['created_by'] === $userId) {
            throw new DomainException('Pembuat order tidak boleh menolak order yang sama.');
        }
        if (!$this->orders->changeSalesOrderStatus($id, 'PendingApproval', 'Cancelled', ['reason' => $reason])) {
            throw new DomainException('Order tidak dapat ditolak pada status sekarang.');
        }
    }

    public function cancelSalesOrder(int $id, int $userId, string $role): void
    {
        if (!in_array($role, ['Admin', 'Sales'], true)) {
            throw new DomainException('Anda tidak berwenang membatalkan Sales Order.');
        }
        $order = $this->requiredSo($id);
        SalesOrder::fromRecord($order)->transitionTo('Cancelled');
        if ($role === 'Sales' && (int) $order['created_by'] !== $userId) {
            throw new DomainException('Sales hanya dapat membatalkan order miliknya sendiri.');
        }
        if (!in_array($order['status'], ['Draft', 'PendingApproval', 'Approved'], true)) {
            throw new DomainException('Sales Order tidak dapat dibatalkan pada status sekarang.');
        }
        if (!$this->orders->changeSalesOrderStatus($id, $order['status'], 'Cancelled')) {
            throw new DomainException('Status order berubah saat diproses.');
        }
    }
    public function fulfillSalesOrder(int $id, int $userId, string $role): void
    {
        if (!in_array($role, ['Admin','WarehouseStaff'], true)) {
            throw new DomainException('Anda tidak berwenang memproses pengeluaran barang.');
        }$o = $this->requiredSo($id);
        SalesOrder::fromRecord($o)->transitionTo('Fulfilled');
        if ($o['status'] !== 'Approved') {
            throw new DomainException('Goods issue hanya dapat diproses untuk order Disetujui.');
        }$this->orders->begin();
        try {
            foreach ($o['items'] as $item) {
                $before = $this->orders->lockStock((int)$item['product_id'], (int)$o['warehouse_id']);
                $after = $before - (int)$item['quantity'];
                if ($after < 0) {
                    throw new DomainException('Stok ' . $item['sku'] . ' tidak mencukupi.');
                }$this->orders->saveStock((int)$item['product_id'], (int)$o['warehouse_id'], $after);
                $this->orders->addLedger($this->ledger($item, $o, 'Issue', $before, $after, $userId, 'SO'));
            }
            if (!$this->orders->changeSalesOrderStatus($id, 'Approved', 'Fulfilled', ['fulfilled' => true])) {
                throw new DomainException('Status order berubah saat diproses.');
            }$this->orders->commit();
        } catch (Throwable $e) {
            if ($this->orders->inTransaction()) {
                $this->orders->rollback();
            }
            throw $e;
        }
    }
    public function orderPurchaseOrder(int $id, string $role): void
    {
        if (!in_array($role, ['Admin','WarehouseStaff'], true)) {
            throw new DomainException('Anda tidak berwenang mengirim Purchase Order.');
        }
        if (!$this->orders->changePurchaseOrderStatus($id, ['Draft'], 'Ordered')) {
            throw new DomainException('PO hanya dapat dikirim dari status Draft.');
        }
    }

    public function cancelPurchaseOrder(int $id, string $role): void
    {
        if (!in_array($role, ['Admin', 'WarehouseStaff'], true)) {
            throw new DomainException('Anda tidak berwenang membatalkan Purchase Order.');
        }
        if (
            !$this->orders->changePurchaseOrderStatus(
                $id,
                ['Draft', 'Ordered', 'PartiallyReceived'],
                'Cancelled'
            )
        ) {
            throw new DomainException('Purchase Order tidak dapat dibatalkan pada status sekarang.');
        }
    }
    public function receivePurchaseOrder(int $id, array $received, int $userId, string $role): void
    {
        if (!in_array($role, ['Admin','WarehouseStaff'], true)) {
            throw new DomainException('Anda tidak berwenang menerima barang.');
        }$o = $this->requiredPo($id);
        if (!in_array($o['status'], ['Ordered','PartiallyReceived'], true)) {
            throw new DomainException('PO tidak dapat diterima pada status sekarang.');
        }$this->orders->begin();
        try {
            $complete = true;
            foreach ($o['items'] as $item) {
                $qty = (int)($received[$item['id']] ?? 0);
                $remaining = (int)$item['quantity'] - (int)$item['received_quantity'];
                if ($qty < 0 || $qty > $remaining) {
                    throw new DomainException('Jumlah penerimaan ' . $item['sku'] . ' tidak valid.');
                }
                if ($qty > 0) {
                    $before = $this->orders->lockStock((int)$item['product_id'], (int)$o['warehouse_id']);
                    $after = $before + $qty;
                    $this->orders->saveStock((int)$item['product_id'], (int)$o['warehouse_id'], $after);
                    $this->orders->updateReceivedQuantity((int)$item['id'], $qty);
                    $copy = $item;
                    $copy['quantity'] = $qty;
                    $this->orders->addLedger($this->ledger($copy, $o, 'Receipt', $before, $after, $userId, 'PO'));
                }
                if ($qty < $remaining) {
                    $complete = false;
                }
            }
            if (!$this->orders->changePurchaseOrderStatus($id, ['Ordered','PartiallyReceived'], $complete ? 'Received' : 'PartiallyReceived')) {
                throw new DomainException('Status PO berubah saat diproses.');
            }$this->orders->commit();
        } catch (Throwable $e) {
            if ($this->orders->inTransaction()) {
                $this->orders->rollback();
            }
            throw $e;
        }
    }
    private function requiredSo(int $id): array
    {
        $o = $this->orders->salesOrder($id);
        if (!$o) {
            throw new DomainException('Sales Order tidak ditemukan.');
        }
        return $o;
    }
    private function requiredPo(int $id): array
    {
        $o = $this->orders->purchaseOrder($id);
        if (!$o) {
            throw new DomainException('Purchase Order tidak ditemukan.');
        }
        return $o;
    }
    private function ledger(array $item, array $o, string $type, int $before, int $after, int $user, string $ref): array
    {
        return ['product_id' => (int)$item['product_id'],'warehouse_id' => (int)$o['warehouse_id'],'movement_type' => $type,'quantity' => (int)$item['quantity'],'reference_type' => $ref,'reference_id' => (int)$o['id'],'performed_by' => $user,'quantity_before' => $before,'quantity_after' => $after,'notes' => $type === 'Issue' ? 'Pengeluaran dari Sales Order' : 'Penerimaan dari Purchase Order'];
    }
}
