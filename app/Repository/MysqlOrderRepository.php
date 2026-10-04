<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Exception\InvalidReceiptQuantity;
use App\Support\ListOptions;

/**
 * Implementasi repository untuk MySQL.
 *
 * Semua input dari request diteruskan melalui prepared statement.
 */
final class MysqlOrderRepository implements OrderRepositoryInterface
{
    use MysqlOrderReadOperations;

    public function __construct(
        private PDO $pdo
    ) {
    }

    // ============================================================
    // TRANSACTION
    // ============================================================

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    // ============================================================
    // SALES ORDER
    // ============================================================

    public function createSalesOrder(array $header, array $items): int
    {
        $sql = <<<'SQL'
            INSERT INTO sales_orders (
                order_number,
                customer_id,
                warehouse_id,
                status,
                order_date,
                created_by
            ) VALUES (
                :number,
                :customer,
                :warehouse,
                'Draft',
                CURRENT_DATE,
                :user
            )
        SQL;

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            'number'   => $header['order_number'],
            'customer' => $header['customer_id'],
            'warehouse' => $header['warehouse_id'],
            'user'     => $header['created_by'],
        ]);

        $orderId = (int) $this->pdo->lastInsertId();

        $itemStatement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO sales_order_items (
                sales_order_id,
                product_id,
                quantity,
                selling_price
            ) VALUES (
                :order_id,
                :product_id,
                :quantity,
                :price
            )
        SQL);

        foreach ($items as $item) {
            $itemStatement->execute([
                'order_id'  => $orderId,
                'product_id' => $item['product_id'],
                'quantity'  => $item['quantity'],
                'price'     => $item['price'],
            ]);
        }

        return $orderId;
    }

    public function salesOrder(int $id): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT
                so.id,
                so.order_number,
                so.customer_id,
                so.warehouse_id,
                so.status,
                so.order_date,
                so.created_by,
                so.approved_by,
                c.name AS customer,
                w.name AS warehouse,
                u.name AS creator
            FROM sales_orders AS so
            JOIN customers AS c
                ON c.id = so.customer_id
            JOIN warehouses AS w
                ON w.id = so.warehouse_id
            JOIN users AS u
                ON u.id = so.created_by
            WHERE so.id = :id
        SQL);

        $statement->execute([
            'id' => $id,
        ]);

        $order = $statement->fetch();

        if (!$order) {
            return null;
        }

        $itemStatement = $this->pdo->prepare(<<<'SQL'
            SELECT
                soi.id,
                soi.sales_order_id,
                soi.product_id,
                soi.quantity,
                soi.selling_price,
                p.sku,
                p.name AS product_name
            FROM sales_order_items AS soi
            JOIN products AS p
                ON p.id = soi.product_id
            WHERE soi.sales_order_id = :id
            ORDER BY soi.id ASC
        SQL);

        $itemStatement->execute([
            'id' => $id,
        ]);

        $order['items'] = $itemStatement->fetchAll();

        return $order;
    }

    public function salesOrders(array $filters): array
    {
        return $this->listOrders($filters, 'sales');
    }

    public function changeSalesOrderStatus(
        int $id,
        string $from,
        string $to,
        array $audit = []
    ): bool {
        $fields = [
            'status = :to',
        ];

        $params = [
            'id'   => $id,
            'from' => $from,
            'to'   => $to,
        ];

        if (isset($audit['approved_by'])) {
            $fields[] = 'approved_by = :approved_by';
            $fields[] = 'approved_at = CURRENT_TIMESTAMP';

            $params['approved_by'] = $audit['approved_by'];
        }

        if (isset($audit['submitted'])) {
            $fields[] = 'submitted_at = CURRENT_TIMESTAMP';
        }

        if (isset($audit['fulfilled'])) {
            $fields[] = 'fulfilled_at = CURRENT_TIMESTAMP';
        }

        if (array_key_exists('reason', $audit)) {
            $fields[] = 'rejection_reason = :reason';
            $params['reason'] = $audit['reason'];
        }

        $sql = sprintf(
            'UPDATE sales_orders SET %s WHERE id = :id AND status = :from',
            implode(', ', $fields)
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() === 1;
    }

    // ============================================================
    // PURCHASE ORDER
    // ============================================================

    public function createPurchaseOrder(array $header, array $items): int
    {
        $sql = <<<'SQL'
            INSERT INTO purchase_orders (
                order_number,
                supplier_id,
                warehouse_id,
                status,
                order_date,
                created_by
            ) VALUES (
                :number,
                :supplier,
                :warehouse,
                'Draft',
                CURRENT_DATE,
                :user
            )
        SQL;

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            'number'   => $header['order_number'],
            'supplier' => $header['supplier_id'],
            'warehouse' => $header['warehouse_id'],
            'user'     => $header['created_by'],
        ]);

        $orderId = (int) $this->pdo->lastInsertId();

        $itemStatement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO purchase_order_items (
                purchase_order_id,
                product_id,
                quantity,
                received_quantity,
                purchase_price
            ) VALUES (
                :order_id,
                :product_id,
                :quantity,
                0,
                :price
            )
        SQL);

        foreach ($items as $item) {
            $itemStatement->execute([
                'order_id'  => $orderId,
                'product_id' => $item['product_id'],
                'quantity'  => $item['quantity'],
                'price'     => $item['price'],
            ]);
        }

        return $orderId;
    }

    public function purchaseOrder(int $id): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT
                po.id,
                po.order_number,
                po.supplier_id,
                po.warehouse_id,
                po.status,
                po.order_date,
                po.created_by,
                s.name AS supplier,
                w.name AS warehouse
            FROM purchase_orders AS po
            JOIN suppliers AS s
                ON s.id = po.supplier_id
            JOIN warehouses AS w
                ON w.id = po.warehouse_id
            WHERE po.id = :id
        SQL);

        $statement->execute([
            'id' => $id,
        ]);

        $order = $statement->fetch();

        if (!$order) {
            return null;
        }

        $itemStatement = $this->pdo->prepare(<<<'SQL'
            SELECT
                poi.id,
                poi.purchase_order_id,
                poi.product_id,
                poi.quantity,
                poi.received_quantity,
                poi.purchase_price,
                p.sku,
                p.name AS product_name
            FROM purchase_order_items AS poi
            JOIN products AS p
                ON p.id = poi.product_id
            WHERE poi.purchase_order_id = :id
            ORDER BY poi.id ASC
        SQL);

        $itemStatement->execute([
            'id' => $id,
        ]);

        $order['items'] = $itemStatement->fetchAll();

        return $order;
    }

    public function purchaseOrders(array $filters): array
    {
        return $this->listOrders($filters, 'purchase');
    }

    public function salesOrderCount(array $filters): int
    {
        return $this->countOrders($filters, 'sales');
    }

    public function purchaseOrderCount(array $filters): int
    {
        return $this->countOrders($filters, 'purchase');
    }

    /**
     * Builds the shared filtered order list while keeping SQL identifiers in a
     * fixed, internal configuration so request values can never become SQL.
     */
    public function changePurchaseOrderStatus(
        int $id,
        array $from,
        string $to
    ): bool {
        if ($from === []) {
            return false;
        }

        $placeholders = implode(
            ', ',
            array_fill(0, count($from), '?')
        );

        $sql = <<<SQL
            UPDATE purchase_orders
            SET
                status = ?,
                ordered_at = IF(? = 'Ordered', CURRENT_TIMESTAMP, ordered_at),
                received_at = IF(? = 'Received', CURRENT_TIMESTAMP, received_at)
            WHERE id = ?
              AND status IN ($placeholders)
        SQL;

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            ...[$to, $to, $to, $id],
            ...$from,
        ]);

        return $statement->rowCount() === 1;
    }

    // ============================================================
    // STOCK
    // ============================================================

    public function lockStock(int $productId, int $warehouseId): int
    {
        $ensure = $this->pdo->prepare(
            'INSERT IGNORE INTO product_stocks (product_id, warehouse_id, quantity)
             VALUES (:product_id, :warehouse_id, 0)'
        );
        $ensure->execute(['product_id' => $productId, 'warehouse_id' => $warehouseId]);
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT quantity
            FROM product_stocks
            WHERE product_id = :product_id
              AND warehouse_id = :warehouse_id
            FOR UPDATE
        SQL);

        $statement->execute([
            'product_id'  => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        $quantity = $statement->fetchColumn();

        return $quantity === false
            ? 0
            : (int) $quantity;
    }

    public function saveStock(
        int $productId,
        int $warehouseId,
        int $quantity
    ): void {
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO product_stocks (
                product_id,
                warehouse_id,
                quantity
            ) VALUES (
                :product_id,
                :warehouse_id,
                :quantity
            )
            ON DUPLICATE KEY UPDATE
                quantity = VALUES(quantity),
                updated_at = CURRENT_TIMESTAMP
        SQL);

        $statement->execute([
            'product_id'  => $productId,
            'warehouse_id' => $warehouseId,
            'quantity'    => $quantity,
        ]);
    }

    public function addLedger(array $data): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO stock_ledger (
                product_id,
                warehouse_id,
                movement_type,
                quantity,
                reference_type,
                reference_id,
                performed_by,
                quantity_before,
                quantity_after,
                notes
            ) VALUES (
                :product_id,
                :warehouse_id,
                :movement_type,
                :quantity,
                :reference_type,
                :reference_id,
                :performed_by,
                :quantity_before,
                :quantity_after,
                :notes
            )
        SQL);

        $statement->execute($data);
    }

    public function updateReceivedQuantity(
        int $id,
        int $quantity
    ): void {
        $statement = $this->pdo->prepare(<<<'SQL'
            UPDATE purchase_order_items
            SET received_quantity = received_quantity + :quantity_increment
            WHERE id = :id
              AND received_quantity + :quantity_limit <= quantity
        SQL);

        $statement->execute([
            'quantity_increment' => $quantity,
            'quantity_limit' => $quantity,
            'id'       => $id,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new InvalidReceiptQuantity(
                'Jumlah penerimaan melebihi jumlah pesanan.'
            );
        }
    }

    // ============================================================
    // STOCK LEDGER
    // ============================================================
}
