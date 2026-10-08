<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\QueryBuilder;
use App\Support\ListOptions;
use InvalidArgumentException;
use PDO;

final class MysqlInventoryRepository implements InventoryRepositoryInterface
{
    use MysqlInventoryMasterOperations;

    private const OPTION_COLUMNS = 'id, name';
    private QueryBuilder $query;

    public function __construct(private readonly PDO $pdo)
    {
        $this->query = new QueryBuilder($pdo);
    }

    public function findUserByEmail(string $email): ?array
    {
        return $this->query->table('users', 'u')->select([
            'u.id',
            'u.name',
            'u.email',
            'u.password_hash',
            'u.role',
            'u.is_active',
            'u.created_at',
            'u.updated_at',
        ])->where('u.email', '=', $email)->first();
    }

    public function dashboard(string $role, int $userId): array
    {
        $scalar = function (string $sql, array $parameters = []): float {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
            return (float) $statement->fetchColumn();
        };
        $lowStockProducts = 'SELECT COUNT(*) FROM (
            SELECT p.id
            FROM products p
            LEFT JOIN product_stocks ps ON ps.product_id = p.id
            WHERE p.is_active = 1
            GROUP BY p.id, p.reorder_point
            HAVING COALESCE(SUM(ps.quantity), 0) <= p.reorder_point
        ) AS low_stock_products';
        if ($role === 'Sales') {
            return [
                'Order saya' => $scalar('SELECT COUNT(*) FROM sales_orders WHERE created_by=:id', ['id' => $userId]),
                'Draft' => $scalar("SELECT COUNT(*) FROM sales_orders WHERE created_by=:id AND status='Draft'", ['id' => $userId]),
                'Pending approval' => $scalar("SELECT COUNT(*) FROM sales_orders WHERE created_by=:id AND status='PendingApproval'", ['id' => $userId]),
                'Approved' => $scalar("SELECT COUNT(*) FROM sales_orders WHERE created_by=:id AND status='Approved'", ['id' => $userId]),
                'Fulfilled' => $scalar("SELECT COUNT(*) FROM sales_orders WHERE created_by=:id AND status='Fulfilled'", ['id' => $userId]),
                'Cancelled' => $scalar("SELECT COUNT(*) FROM sales_orders WHERE created_by=:id AND status='Cancelled'", ['id' => $userId]),
            ];
        }
        if ($role === 'WarehouseStaff') {
            return [
                'Goods receipt' => $scalar("SELECT COUNT(*) FROM purchase_orders WHERE status IN ('Ordered','PartiallyReceived')"),
                'Goods issue' => $scalar("SELECT COUNT(*) FROM sales_orders WHERE status='Approved'"),
                'Low stock' => $scalar($lowStockProducts),
                'Movement hari ini' => $scalar('SELECT COUNT(*) FROM stock_ledger WHERE created_at>=CURRENT_DATE'),
            ];
        }
        return [
            'Nilai inventori' => $scalar(
                'SELECT COALESCE(SUM(product_inventory.inventory_value), 0)
                 FROM (
                    SELECT p.id,
                           p.purchase_price * COALESCE(SUM(ps.quantity), 0) AS inventory_value
                    FROM products p
                    LEFT JOIN product_stocks ps ON ps.product_id = p.id
                    GROUP BY p.id, p.purchase_price
                 ) AS product_inventory'
            ),
            'Produk aktif' => $scalar('SELECT COUNT(*) FROM products WHERE is_active=1'),
            'Low stock' => $scalar($lowStockProducts),
            'SO menunggu approval' => $scalar("SELECT COUNT(*) FROM sales_orders WHERE status='PendingApproval'"),
            'PO menunggu penerimaan' => $scalar("SELECT COUNT(*) FROM purchase_orders WHERE status IN ('Ordered','PartiallyReceived')"),
        ];
    }

    public function products(array $filter = []): array
    {
        $sortColumns = [
            'sku' => 'p.sku',
            'name' => 'p.name',
            'category' => 'c.name',
            'unit' => 'p.unit',
            'selling_price' => 'p.selling_price',
            'total_stock' => 'total_stock',
            'warehouse_stocks' => 'warehouse_stocks',
            'reorder_point' => 'p.reorder_point',
        ];
        $options = ListOptions::fromRequest($filter, array_keys($sortColumns), 'name');
        $term = '%' . trim((string) ($filter['q'] ?? '')) . '%';
        $stockFilter = in_array(($filter['stock'] ?? ''), ['low', 'normal'], true)
            ? $filter['stock']
            : '';
        $activeFilter = in_array((string) ($filter['active'] ?? ''), ['0', '1'], true)
            ? (string) $filter['active']
            : '';
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.sku, p.name, p.unit, p.selling_price,
                    p.reorder_point, p.image_path, p.is_active, c.name AS category,
                    COALESCE(SUM(ps.quantity), 0) AS total_stock,
                    GROUP_CONCAT(CONCAT(w.name, CHAR(58, 32), ps.quantity) ORDER BY w.name SEPARATOR 0x2C20) AS warehouse_stocks
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_stocks ps ON ps.product_id = p.id
             LEFT JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE (:term_empty = 1 OR p.name LIKE :name OR p.sku LIKE :sku)
               AND (:category_id = 0 OR p.category_id = :category_value)
               AND (:active_filter = \'\' OR p.is_active = :active_value)
             GROUP BY p.id, p.sku, p.name, p.unit, p.selling_price,
                      p.reorder_point, p.image_path, p.is_active, c.name
             HAVING (:stock_filter = \'\'
                 OR (:stock_low = 1 AND total_stock <= p.reorder_point)
                 OR (:stock_normal = 1 AND total_stock > p.reorder_point))
             ORDER BY ' . $sortColumns[$options->sort] . ' ' . $options->sqlDirection() . ', p.id ASC
             LIMIT ' . $options->perPage . ' OFFSET ' . $options->offset()
        );
        $statement->execute([
            'term_empty' => trim((string) ($filter['q'] ?? '')) === '' ? 1 : 0,
            'name' => $term,
            'sku' => $term,
            'category_id' => (int) ($filter['category_id'] ?? 0),
            'category_value' => (int) ($filter['category_id'] ?? 0),
            'active_filter' => $activeFilter,
            'active_value' => $activeFilter === '' ? 0 : (int) $activeFilter,
            'stock_filter' => $stockFilter,
            'stock_low' => $stockFilter === 'low' ? 1 : 0,
            'stock_normal' => $stockFilter === 'normal' ? 1 : 0,
        ]);
        return $statement->fetchAll();
    }

    public function activeOptions(string $table): array
    {
        $columns = [
            'categories' => self::OPTION_COLUMNS,
            'warehouses' => self::OPTION_COLUMNS,
            'suppliers' => self::OPTION_COLUMNS,
            'customers' => self::OPTION_COLUMNS,
        ];
        if (!isset($columns[$table])) {
            throw new InvalidArgumentException('Tabel opsi tidak diizinkan.');
        }
        return $this->pdo->query(
            $table === 'suppliers'
                ? 'SELECT s.id, s.name, s.category_id, c.name AS category_name
                   FROM suppliers AS s
                   JOIN categories AS c ON c.id = s.category_id
                   WHERE s.is_active = 1 AND c.is_active = 1
                   ORDER BY s.name ASC, s.id ASC'
                : 'SELECT ' . $columns[$table] . ' FROM ' . $table . ' WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
    }

    public function activeProducts(): array
    {
        return $this->pdo->query(
            'SELECT id, sku, name, category_id, purchase_price, selling_price
             FROM products
             WHERE is_active = 1
             ORDER BY sku ASC, name ASC, id ASC'
        )->fetchAll();
    }

    public function findProduct(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, sku, name, category_id, unit, purchase_price,
                    selling_price, reorder_point, image_path, is_active
             FROM products WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function productDetail(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.sku, p.name, p.unit, p.purchase_price,
                    p.selling_price, p.reorder_point, p.image_path,
                    p.is_active, c.name AS category,
                    w.id AS warehouse_id, w.name AS warehouse,
                    COALESCE(ps.quantity, 0) AS quantity
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_stocks ps ON ps.product_id = p.id
             LEFT JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE p.id = :id
             ORDER BY w.name'
        );
        $statement->execute(['id' => $id]);
        $rows = $statement->fetchAll();
        if ($rows === []) {
            return null;
        }
        $product = $rows[0];
        $product['warehouses'] = array_values(array_filter(
            array_map(
                static fn(array $row): ?array => $row['warehouse_id'] === null
                    ? null
                    : ['id' => (int) $row['warehouse_id'], 'name' => $row['warehouse'], 'quantity' => (int) $row['quantity']],
                $rows
            )
        ));
        return $product;
    }

    public function createProduct(array $data): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO products (sku, name, category_id, unit, purchase_price, selling_price, reorder_point, image_path)
             VALUES (:sku, :name, :category_id, :unit, :purchase_price, :selling_price, :reorder_point, :image_path)'
        );
        $statement->execute($data);
    }

    public function updateProduct(int $id, array $data): void
    {
        $data['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE products SET sku=:sku, name=:name, category_id=:category_id,
             unit=:unit, purchase_price=:purchase_price, selling_price=:selling_price,
             reorder_point=:reorder_point,
             image_path=COALESCE(:image_path, image_path) WHERE id=:id'
        );
        $statement->execute($data);
    }

    public function setProductActive(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE products SET is_active=:active WHERE id=:id');
        $statement->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }

    public function productCount(array $filter = []): int
    {
        $activeFilter = in_array((string) ($filter['active'] ?? ''), ['0', '1'], true)
            ? (string) $filter['active']
            : '';
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_stocks ps ON ps.product_id = p.id
             WHERE (:term_empty = 1 OR p.name LIKE :name OR p.sku LIKE :sku)
               AND (:category_id = 0 OR p.category_id = :category_value)
               AND (:active_filter = \'\' OR p.is_active = :active_value)
             GROUP BY p.id, p.reorder_point
             HAVING (:stock_filter = \'\'
                 OR (:stock_low = 1 AND COALESCE(SUM(ps.quantity), 0) <= p.reorder_point)
                 OR (:stock_normal = 1 AND COALESCE(SUM(ps.quantity), 0) > p.reorder_point))'
        );
        $term = trim((string) ($filter['q'] ?? ''));
        $stockFilter = in_array(($filter['stock'] ?? ''), ['low', 'normal'], true)
            ? $filter['stock']
            : '';
        $statement->execute([
            'term_empty' => $term === '' ? 1 : 0,
            'name' => '%' . $term . '%',
            'sku' => '%' . $term . '%',
            'category_id' => (int) ($filter['category_id'] ?? 0),
            'category_value' => (int) ($filter['category_id'] ?? 0),
            'active_filter' => $activeFilter,
            'active_value' => $activeFilter === '' ? 0 : (int) $activeFilter,
            'stock_filter' => $stockFilter,
            'stock_low' => $stockFilter === 'low' ? 1 : 0,
            'stock_normal' => $stockFilter === 'normal' ? 1 : 0,
        ]);
        return count($statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function findStockForUpdate(int $productId, int $warehouseId): ?array
    {
        $ensure = $this->pdo->prepare(
            'INSERT IGNORE INTO product_stocks (product_id, warehouse_id, quantity)
             VALUES (:product_id, :warehouse_id, 0)'
        );
        $ensure->execute(['product_id' => $productId, 'warehouse_id' => $warehouseId]);
        return $this->query->table('product_stocks', 'ps')->select([
            'ps.product_id',
            'ps.warehouse_id',
            'ps.quantity',
            'ps.updated_at',
        ])->where('ps.product_id', '=', $productId)
            ->where('ps.warehouse_id', '=', $warehouseId)->forUpdate()->first();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function setStock(int $productId, int $warehouseId, int $quantity): void
    {
        $statement = $this->pdo->prepare('INSERT INTO product_stocks(product_id,warehouse_id,quantity) VALUES(:product,:warehouse,:quantity) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),updated_at=CURRENT_TIMESTAMP');
        $statement->execute(['product' => $productId, 'warehouse' => $warehouseId, 'quantity' => $quantity]);
    }

    public function addLedger(array $data): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO stock_ledger (
                product_id, warehouse_id, movement_type, quantity,
                reference_type, reference_id, performed_by,
                quantity_before, quantity_after, notes
             ) VALUES (
                :product_id, :warehouse_id, :movement_type, :quantity,
                :reference_type, :reference_id, :performed_by,
                :quantity_before, :quantity_after, :notes
             )'
        );
        $statement->execute($data);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
