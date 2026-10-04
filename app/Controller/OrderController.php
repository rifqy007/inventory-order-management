<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\MysqlInventoryRepository;
use App\Support\Http;
use PDO;

final class OrderController
{
    public function __construct(
        private MysqlInventoryRepository $repo,
        private PDO $pdo
    ) {
    }

    public function list(string $type): void
    {
        $u = Http::requireRole($type === 'po'
            ? ['Admin', 'WarehouseStaff']
            : ['Admin', 'Sales', 'WarehouseStaff']);

        $table = $type === 'po'
            ? 'purchase_orders'
            : 'sales_orders';

        $rows = $this->repo->all($table);

        if (
            $type === 'so'
            && $u['role'] === 'Sales'
        ) {
            $rows = array_values(
                array_filter(
                    $rows,
                    fn($r) => (int) $r['created_by'] === $u['id']
                )
            );
        }

        Http::view('orders/index', compact('type', 'rows'));
    }

    public function approve(int $id): void
    {
        $u = Http::requireRole(['Admin']);

        Http::verifyCsrf();

        $s = $this->pdo->prepare(
            "UPDATE sales_orders
             SET status = 'Approved',
                 approved_by = :u
             WHERE id = :id
               AND status = 'PendingApproval'
               AND created_by <> :u"
        );

        $s->execute([
            'u' => $u['id'],
            'id' => $id,
        ]);

        Http::flash(
            $s->rowCount() ? 'success' : 'error',
            $s->rowCount()
                ? 'Order disetujui.'
                : 'Order tidak dapat disetujui.'
        );

        Http::redirect('/sales-orders');
    }
}
