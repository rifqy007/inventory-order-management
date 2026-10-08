<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repository\MysqlOrderRepository;
use App\Repository\MysqlInventoryRepository;
use App\Service\OrderService;
use App\Support\Database;
use DomainException;
use PDO;
use PHPUnit\Framework\TestCase;

final class OrderWorkflowIntegrationTest extends TestCase
{
    private PDO $pdo;
    private MysqlOrderRepository $repository;
    private OrderService $service;
    private int $productId = 30;
    private int $warehouseId = 1;
    private int $originalStock;
    private array $createdSalesOrders = [];
    private array $createdPurchaseOrders = [];

    protected function setUp(): void
    {
        putenv('DB_HOST=' . (getenv('TEST_DB_HOST') ?: 'db-test'));
        putenv('DB_PORT=' . (getenv('TEST_DB_PORT') ?: '3306'));
        putenv('DB_NAME=' . (getenv('TEST_DB_NAME') ?: 'inventory'));
        putenv('DB_USER=' . (getenv('TEST_DB_USER') ?: 'inventory'));
        putenv('DB_PASS=' . (getenv('TEST_DB_PASS') ?: 'inventory_secret'));
        $this->pdo = Database::connect();
        $this->repository = new MysqlOrderRepository($this->pdo);
        $this->service = new OrderService($this->repository);

        $statement = $this->pdo->prepare(
            'SELECT quantity FROM product_stocks WHERE product_id = :product AND warehouse_id = :warehouse'
        );
        $statement->execute(['product' => $this->productId, 'warehouse' => $this->warehouseId]);
        $this->originalStock = (int) $statement->fetchColumn();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdSalesOrders as $id) {
            $this->deleteOrder('sales_orders', 'sales_order_items', $id, 'SO');
        }
        foreach ($this->createdPurchaseOrders as $id) {
            $this->deleteOrder('purchase_orders', 'purchase_order_items', $id, 'PO');
        }
        $this->repository->saveStock($this->productId, $this->warehouseId, $this->originalStock);
    }

    public function test_demo_seed_meets_required_volume(): void
    {
        self::assertGreaterThanOrEqual(30, $this->scalar('SELECT COUNT(*) FROM products'));
        self::assertGreaterThanOrEqual(2, $this->scalar('SELECT COUNT(*) FROM warehouses'));
        self::assertGreaterThanOrEqual(25, $this->scalar('SELECT COUNT(*) FROM purchase_orders') + $this->scalar('SELECT COUNT(*) FROM sales_orders'));
        self::assertGreaterThanOrEqual(2, $this->scalar("SELECT COUNT(*) FROM users WHERE role = 'Sales'"));
        self::assertGreaterThanOrEqual(2, $this->scalar("SELECT COUNT(*) FROM users WHERE role = 'WarehouseStaff'"));
    }

    public function test_product_sku_search_and_warehouse_stock_summary(): void
    {
        $repository = new MysqlInventoryRepository($this->pdo);
        $filters = ['q' => 'SKU-030', 'page' => 1, 'per_page' => 10, 'direction' => 'asc'];

        self::assertSame(1, $repository->productCount($filters));
        $products = $repository->products($filters);
        self::assertSame('SKU-030', $products[0]['sku']);
        self::assertNotEmpty($products[0]['warehouse_stocks']);
        $orderProducts = $repository->activeProducts();
        self::assertSame('SKU-001', $orderProducts[0]['sku']);
        self::assertSame('SKU-002', $orderProducts[1]['sku']);
        self::assertSame('SKU-010', $orderProducts[9]['sku']);
        $detail = $repository->productDetail((int) $products[0]['id']);
        self::assertCount(2, $detail['warehouses']);
    }

    public function test_inventory_repository_options_master_data_and_dashboard_queries(): void
    {
        $inventory = new MysqlInventoryRepository($this->pdo);
        $email = (string) $this->pdo->query('SELECT email FROM users ORDER BY id LIMIT 1')->fetchColumn();
        self::assertNotNull($inventory->findUserByEmail($email));
        self::assertNotEmpty($inventory->activeOptions('categories'));
        self::assertNotEmpty($inventory->activeProducts());
        self::assertNotEmpty($inventory->all('categories'));
        self::assertNotEmpty($inventory->dashboard('Admin', 1));
        self::assertNotEmpty($inventory->dashboard('Sales', 1));
        self::assertNotEmpty($inventory->dashboard('WarehouseStaff', 1));

        $name = 'Coverage-' . bin2hex(random_bytes(6));
        $inventory->insertMaster('categories', ['name' => $name, 'description' => 'Initial']);
        $row = $this->pdo->prepare('SELECT id FROM categories WHERE name = :name');
        $row->execute(['name' => $name]);
        $id = (int) $row->fetchColumn();

        try {
            self::assertSame($name, $inventory->findMasterById('categories', $id)['name']);
            self::assertSame(1, $inventory->masterCount('categories', $name));
            $masterRows = $inventory->masterRows('categories', [
                'q' => $name,
                'sort' => 'name',
                'direction' => 'desc',
                'per_page' => 10,
                'page' => 1,
            ]);
            self::assertCount(1, $masterRows);
            self::assertSame($name, $masterRows[0]['name']);
            $inventory->updateMaster('categories', $id, ['name' => $name, 'description' => 'Updated']);
            $inventory->setMasterActive('categories', $id, false);
            self::assertSame(0, (int) $inventory->findMasterById('categories', $id)['is_active']);
            $inventory->setMasterActive('categories', $id, true);
        } finally {
            $delete = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
            $delete->execute(['id' => $id]);
        }
    }

    public function test_order_lists_search_by_number_filter_status_and_sort(): void
    {
        $salesFilters = [
            'owner' => 0,
            'status' => 'PendingApproval',
            'q' => 'SO-001',
            'page' => 1,
            'per_page' => 10,
            'direction' => 'desc',
        ];
        self::assertGreaterThanOrEqual(1, $this->repository->salesOrderCount($salesFilters));
        self::assertSame('SO-001', $this->repository->salesOrders($salesFilters)[0]['order_number']);

        $purchaseFilters = [
            'status' => 'Ordered',
            'q' => 'PO-001',
            'page' => 1,
            'per_page' => 10,
            'direction' => 'desc',
        ];
        self::assertGreaterThanOrEqual(1, $this->repository->purchaseOrderCount($purchaseFilters));
        self::assertSame('PO-001', $this->repository->purchaseOrders($purchaseFilters)[0]['order_number']);

        self::assertIsArray($this->repository->ledger(['start' => '2000-01-01', 'end' => '2099-12-31']));
        self::assertIsArray($this->repository->orderReport(['start' => '2000-01-01', 'end' => '2099-12-31']));
    }

    public function test_sales_order_fulfillment_updates_stock_and_ledger(): void
    {
        $this->setStock(100);
        $id = $this->createSalesOrder(5);
        $order = $this->repository->salesOrder($id);
        self::assertCount(1, $order['items']);
        self::assertSame(5, (int) $order['items'][0]['quantity']);
        $this->service->submitSalesOrder($id, 2, 'Sales');
        $this->service->approveSalesOrder($id, 1, 'Admin');
        $this->service->fulfillSalesOrder($id, 4, 'WarehouseStaff');

        self::assertSame('Fulfilled', $this->repository->salesOrder($id)['status']);
        self::assertSame(95, $this->stock());
        self::assertSame(1, $this->ledgerCount('SO', $id));
    }

    public function test_goods_issue_rejects_shortage_and_rolls_back(): void
    {
        $this->setStock(2);
        $id = $this->createSalesOrder(5);
        $this->service->submitSalesOrder($id, 2, 'Sales');
        $this->service->approveSalesOrder($id, 1, 'Admin');

        try {
            $this->service->fulfillSalesOrder($id, 4, 'WarehouseStaff');
            self::fail('Expected goods issue with insufficient stock to fail.');
        } catch (DomainException) {
            self::assertSame(2, $this->stock());
            self::assertSame(0, $this->ledgerCount('SO', $id));
            self::assertSame('Approved', $this->repository->salesOrder($id)['status']);
        }
    }

    public function test_partial_and_full_goods_receipt_update_stock_and_ledger(): void
    {
        $this->setStock(10);
        $number = $this->orderNumber('PO');
        $id = $this->service->createPurchaseOrder(
            ['order_number' => $number, 'supplier_id' => 1, 'warehouse_id' => $this->warehouseId],
            [['product_id' => $this->productId, 'quantity' => 10, 'price' => 10100]],
            4,
            'WarehouseStaff'
        );
        $this->createdPurchaseOrders[] = $id;
        $this->service->orderPurchaseOrder($id, 'WarehouseStaff');
        $order = $this->repository->purchaseOrder($id);
        $itemId = (int) $order['items'][0]['id'];

        $this->service->receivePurchaseOrder($id, [$itemId => 4], 4, 'WarehouseStaff');
        self::assertSame('PartiallyReceived', $this->repository->purchaseOrder($id)['status']);
        self::assertSame(14, $this->stock());
        self::assertSame(1, $this->ledgerCount('PO', $id));

        $this->service->receivePurchaseOrder($id, [$itemId => 6], 4, 'WarehouseStaff');
        self::assertSame('Received', $this->repository->purchaseOrder($id)['status']);
        self::assertSame(20, $this->stock());
        self::assertSame(2, $this->ledgerCount('PO', $id));
    }

    private function createSalesOrder(int $quantity): int
    {
        $id = $this->service->createSalesOrder(
            ['order_number' => $this->orderNumber('SO'), 'customer_id' => 1, 'warehouse_id' => $this->warehouseId],
            [['product_id' => $this->productId, 'quantity' => $quantity, 'price' => 19500]],
            2,
            'Sales'
        );
        $this->createdSalesOrders[] = $id;
        return $id;
    }

    private function orderNumber(string $prefix): string
    {
        return $prefix . '-IT-' . strtoupper(bin2hex(random_bytes(6)));
    }

    private function setStock(int $quantity): void
    {
        $this->repository->saveStock($this->productId, $this->warehouseId, $quantity);
    }

    private function stock(): int
    {
        $statement = $this->pdo->prepare(
            'SELECT quantity FROM product_stocks WHERE product_id = :product AND warehouse_id = :warehouse'
        );
        $statement->execute(['product' => $this->productId, 'warehouse' => $this->warehouseId]);
        return (int) $statement->fetchColumn();
    }

    private function scalar(string $query): int
    {
        return (int) $this->pdo->query($query)->fetchColumn();
    }

    private function ledgerCount(string $referenceType, int $referenceId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM stock_ledger WHERE reference_type = :type AND reference_id = :id'
        );
        $statement->execute(['type' => $referenceType, 'id' => $referenceId]);
        return (int) $statement->fetchColumn();
    }

    private function deleteOrder(string $table, string $itemsTable, int $id, string $reference): void
    {
        $statement = $this->pdo->prepare('DELETE FROM stock_ledger WHERE reference_type = :type AND reference_id = :id');
        $statement->execute(['type' => $reference, 'id' => $id]);
        $statement = $this->pdo->prepare('DELETE FROM ' . $itemsTable . ' WHERE ' . ($reference === 'SO' ? 'sales_order_id' : 'purchase_order_id') . ' = :id');
        $statement->execute(['id' => $id]);
        $statement = $this->pdo->prepare('DELETE FROM ' . $table . ' WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
