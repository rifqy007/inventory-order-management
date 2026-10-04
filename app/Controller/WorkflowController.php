<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrderRepositoryInterface;
use App\Repository\MysqlInventoryRepository;
use App\Service\OrderService;
use App\Support\Http;
use App\Support\ListOptions;
use Throwable;

final class WorkflowController
{
    private const PURCHASE_WORKFLOW_PATH = '/purchase-orders/workflow/';
    private const NOT_FOUND_VIEW = 'errors/404';

    public function __construct(
        private OrderRepositoryInterface $repo,
        private OrderService $service,
        private MysqlInventoryRepository $inventory
    ) {
    }

    public function salesCreateForm(): void
    {
        Http::requireRole(['Admin', 'Sales']);
        Http::view('workflow/sales-create', [
            'customers' => $this->inventory->activeOptions('customers'),
            'warehouses' => $this->inventory->activeOptions('warehouses'),
            'products' => $this->inventory->activeProducts(),
        ]);
    }

    public function salesCreate(): void
    {
        $user = Http::requireRole(['Admin', 'Sales']);
        Http::verifyCsrf();
        try {
            $items = $this->postedItems('selling_price');
            $id = $this->service->createSalesOrder([
                'order_number' => 'SO-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2))),
                'customer_id' => (int) ($_POST['customer_id'] ?? 0),
                'warehouse_id' => (int) ($_POST['warehouse_id'] ?? 0),
            ], $items, $user['id'], $user['role']);
            Http::redirect('/sales-orders/workflow/' . $id);
        } catch (Throwable $e) {
            Http::flash('error', $e instanceof \DomainException ? $e->getMessage() : 'Sales Order gagal dibuat.');
            Http::redirect('/sales-orders/workflow/new');
        }
    }

    public function purchaseCreateForm(): void
    {
        Http::requireRole(['Admin', 'WarehouseStaff']);
        Http::view('workflow/purchase-create', [
            'suppliers' => $this->inventory->activeOptions('suppliers'),
            'warehouses' => $this->inventory->activeOptions('warehouses'),
            'products' => $this->inventory->activeProducts(),
        ]);
    }

    public function purchaseCreate(): void
    {
        $user = Http::requireRole(['Admin', 'WarehouseStaff']);
        Http::verifyCsrf();
        try {
            $id = $this->service->createPurchaseOrder([
                'order_number' => 'PO-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2))),
                'supplier_id' => (int) ($_POST['supplier_id'] ?? 0),
                'warehouse_id' => (int) ($_POST['warehouse_id'] ?? 0),
            ], $this->postedItems('purchase_price'), $user['id'], $user['role']);
            Http::redirect(self::PURCHASE_WORKFLOW_PATH . $id);
        } catch (Throwable $e) {
            Http::flash('error', $e instanceof \DomainException ? $e->getMessage() : 'Purchase Order gagal dibuat.');
            Http::redirect('/purchase-orders/workflow/new');
        }
    }

    private function postedItems(string $priceColumn): array
    {
        $productIds = (array) ($_POST['product_id'] ?? []);
        $quantities = (array) ($_POST['quantity'] ?? []);
        $catalog = [];
        foreach ($this->inventory->activeProducts() as $product) {
            $catalog[(int) $product['id']] = $product;
        }
        $items = [];
        foreach ($productIds as $index => $productId) {
            if (trim((string) $productId) === '') {
                continue;
            }
            $id = (int) $productId;
            if (!isset($catalog[$id])) {
                throw new \DomainException('Produk yang dipilih tidak tersedia.');
            }
            $items[] = [
                'product_id' => $id,
                'quantity' => $quantities[$index] ?? 0,
                'price' => $catalog[$id][$priceColumn],
            ];
        }
        return $items;
    }

    public function salesList(): void
    {
        $u = Http::requireRole(['Admin', 'Sales', 'WarehouseStaff']);
        $filters = [
            'owner' => $u['role'] === 'Sales' ? $u['id'] : 0,
            'status' => (string) ($_GET['status'] ?? ''),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'direction' => (string) ($_GET['direction'] ?? 'desc'),
            'sort' => (string) ($_GET['sort'] ?? 'order_date'),
        ];
        $options = ListOptions::fromRequest($filters, ['order_number', 'customer', 'creator', 'order_date', 'status'], 'order_date');
        $filters['page'] = $options->page;
        $filters['per_page'] = $options->perPage;
        $filters['direction'] = $options->direction;
        $filters['sort'] = $options->sort;

        Http::view(
            'workflow/sales-list',
            [
                'rows' => $this->repo->salesOrders($filters),
                'filters' => $filters,
                'total' => $this->repo->salesOrderCount($filters),
            ]
        );
    }

    public function salesDetail(int $id): void
    {
        $u = Http::requireRole([
            'Admin',
            'Sales',
            'WarehouseStaff',
        ]);

        $o = $this->repo->salesOrder($id);

        if (
            !$o ||
            (
                $u['role'] === 'Sales' &&
                (int) $o['created_by'] !== $u['id']
            )
        ) {
            $this->notFound();
            return;
        }

        Http::view(
            'workflow/sales-detail',
            [
                'order' => $o,
            ]
        );
    }

    public function salesAction(int $id, string $action): void
    {
        $u = Http::requireLogin();

        Http::verifyCsrf();

        try {
            match ($action) {
                'submit' => $this->service->submitSalesOrder(
                    $id,
                    $u['id'],
                    $u['role']
                ),

                'approve' => $this->service->approveSalesOrder(
                    $id,
                    $u['id'],
                    $u['role']
                ),

                'reject' => $this->service->rejectSalesOrder(
                    $id,
                    $u['id'],
                    $u['role'],
                    (string) ($_POST['reason'] ?? '')
                ),

                'cancel' => $this->service->cancelSalesOrder(
                    $id,
                    $u['id'],
                    $u['role']
                ),

                'fulfill' => $this->service->fulfillSalesOrder(
                    $id,
                    $u['id'],
                    $u['role']
                ),

                default => throw new \DomainException(
                    'Aksi tidak dikenal.'
                ),
            };

            Http::flash(
                'success',
                'Sales Order berhasil diproses.'
            );
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
        } catch (Throwable) {
            Http::flash('error', 'Sales Order gagal diproses. Coba lagi.');
        }

        Http::redirect('/sales-orders/workflow/' . $id);
    }

    public function purchaseList(): void
    {
        Http::requireRole([
            'Admin',
            'WarehouseStaff',
        ]);

        $filters = [
            'status' => (string) ($_GET['status'] ?? ''),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'direction' => (string) ($_GET['direction'] ?? 'desc'),
            'sort' => (string) ($_GET['sort'] ?? 'order_date'),
        ];
        $options = ListOptions::fromRequest($filters, ['order_number', 'supplier', 'order_date', 'status'], 'order_date');
        $filters['page'] = $options->page;
        $filters['per_page'] = $options->perPage;
        $filters['direction'] = $options->direction;
        $filters['sort'] = $options->sort;
        Http::view(
            'workflow/purchase-list',
            [
                'rows' => $this->repo->purchaseOrders($filters),
                'filters' => $filters,
                'total' => $this->repo->purchaseOrderCount($filters),
            ]
        );
    }

    public function purchaseDetail(int $id): void
    {
        Http::requireRole([
            'Admin',
            'WarehouseStaff',
        ]);

        $o = $this->repo->purchaseOrder($id);

        if (!$o) {
            $this->notFound();
            return;
        }

        Http::view(
            'workflow/purchase-detail',
            [
                'order' => $o,
            ]
        );
    }

    public function purchaseOrder(int $id): void
    {
        $u = Http::requireRole([
            'Admin',
            'WarehouseStaff',
        ]);

        Http::verifyCsrf();

        try {
            $this->service->orderPurchaseOrder(
                $id,
                $u['role']
            );

            Http::flash(
                'success',
                'Purchase Order berhasil dikirim.'
            );
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
        } catch (Throwable) {
            Http::flash('error', 'Purchase Order gagal diproses. Coba lagi.');
        }

            Http::redirect(self::PURCHASE_WORKFLOW_PATH . $id);
    }

    public function cancelPurchaseOrder(int $id): void
    {
        $user = Http::requireRole(['Admin', 'WarehouseStaff']);
        Http::verifyCsrf();
        try {
            $this->service->cancelPurchaseOrder($id, $user['role']);
            Http::flash('success', 'Purchase Order dibatalkan.');
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
        } catch (Throwable) {
            Http::flash('error', 'Purchase Order gagal dibatalkan. Coba lagi.');
        }
            Http::redirect(self::PURCHASE_WORKFLOW_PATH . $id);
    }

    public function receive(int $id): void
    {
        $u = Http::requireRole([
            'Admin',
            'WarehouseStaff',
        ]);

        Http::verifyCsrf();

        try {
            $this->service->receivePurchaseOrder(
                $id,
                (array) ($_POST['received'] ?? []),
                $u['id'],
                $u['role']
            );

            Http::flash(
                'success',
                'Penerimaan barang berhasil dicatat.'
            );
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
        } catch (Throwable) {
            Http::flash('error', 'Penerimaan barang gagal dicatat. Coba lagi.');
        }

            Http::redirect(self::PURCHASE_WORKFLOW_PATH . $id);
    }

    public function ledger(): void
    {
        Http::requireLogin();
        try {
            [$start, $end] = $this->reportRange();
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
            Http::redirect('/stock-ledger');
        }

        Http::view(
            'workflow/ledger',
            [
                'rows' => $this->repo->ledger(
                    compact('start', 'end')
                ),
                'start' => $start,
                'end' => $end,
            ]
        );
    }

    public function ledgerCsv(): never
    {
        Http::requireRole(['Admin']);
        [$start, $end] = $this->reportRange();

        $rows = $this->repo->ledger(
            compact('start', 'end')
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header(
            'Content-Disposition: attachment; filename="laporan-stok.csv"'
        );

        $out = fopen('php://output', 'w');

        fputcsv(
            $out,
            [
                'Tanggal',
                'SKU',
                'Produk',
                'Gudang',
                'Jenis',
                'Jumlah',
                'Stok Sebelum',
                'Stok Sesudah',
                'Referensi',
                'Petugas',
            ]
        );

        foreach ($rows as $r) {
            fputcsv($out, array_map([$this, 'safeCsvCell'], [
                    $r['created_at'],
                    $r['sku'],
                    $r['product'],
                    $r['warehouse'],
                    $r['movement_type'],
                    $r['quantity'],
                    $r['quantity_before'],
                    $r['quantity_after'],
                    $r['reference_type'] . '-' . $r['reference_id'],
                    $r['performer'],
            ]));
        }

        fclose($out);

        exit;
    }

    public function orderCsv(): never
    {
        Http::requireRole(['Admin']);
        [$start, $end] = $this->reportRange();
        $rows = $this->repo->orderReport(compact('start', 'end'));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="laporan-status-order.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Jenis', 'Nomor', 'Status', 'Tanggal', 'Pihak']);
        foreach ($rows as $row) {
            fputcsv($out, array_map([$this, 'safeCsvCell'], array_values($row)));
        }
        fclose($out);
        exit;
    }

    private function reportRange(): array
    {
        $start = (string) ($_GET['start'] ?? date('Y-m-01'));
        $end = (string) ($_GET['end'] ?? date('Y-m-d'));
        $valid = static fn(string $value): bool => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            && strtotime($value) !== false
            && date('Y-m-d', strtotime($value)) === $value;
        if (!$valid($start) || !$valid($end) || $start > $end) {
            throw new \DomainException('Rentang tanggal laporan tidak valid.');
        }
        return [$start, $end];
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = (string) $value;
        return preg_match('/^[=+@\-\t\r]/', $cell) === 1 ? "'" . $cell : $cell;
    }

    private function notFound(): void
    {
        http_response_code(404);

        Http::view(self::NOT_FOUND_VIEW);
    }
}
