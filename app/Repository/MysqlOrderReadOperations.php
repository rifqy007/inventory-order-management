<?php

declare(strict_types=1);

namespace App\Repository;

use App\Support\ListOptions;
use PDO;

trait MysqlOrderReadOperations
{
    private function listOrders(array $filters, string $type): array
    {
        $definition = $this->orderListDefinition($type);
        $options = ListOptions::fromRequest($filters, array_keys($definition['sort_columns']), 'order_date');
        [$where, $parameters] = $this->orderFilterParts($filters, $definition);
        $orderAlias = $definition['order_alias'];
        $direction = $options->sqlDirection();
        $sql = sprintf('SELECT %s FROM %s WHERE %s ORDER BY %s %s, %s.id %s LIMIT %d OFFSET %d', $definition['select'], $definition['from'], implode(' AND ', $where), $definition['sort_columns'][$options->sort], $direction, $orderAlias, $direction, $options->perPage, $options->offset());
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    private function countOrders(array $filters, string $type): int
    {
        $definition = $this->orderListDefinition($type);
        [$where, $parameters] = $this->orderFilterParts($filters, $definition);
        $statement = $this->pdo->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE %s', $definition['count_from'], implode(' AND ', $where)));
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    private function orderFilterParts(array $filters, array $definition): array
    {
        $orderAlias = $definition['order_alias'];
        $term = trim((string) ($filters['q'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $where = [
            '(:status = \'\' OR ' . $orderAlias . '.status = :status_value)',
            '(:term_empty = 1 OR ' . $orderAlias . '.order_number LIKE :number_search'
                . ' OR ' . $definition['party_alias'] . '.name LIKE :party_search)',
        ];
        $parameters = [
            'status' => $status,
            'status_value' => $status,
            'term_empty' => $term === '' ? 1 : 0,
            'number_search' => '%' . $term . '%',
            'party_search' => '%' . $term . '%',
        ];
        if ($definition['has_owner_filter']) {
            $owner = (int) ($filters['owner'] ?? 0);
            $where[] = '(' . $orderAlias . '.created_by = :owner'
                . ' OR :owner_filter_disabled = 1)';
            $parameters['owner'] = $owner;
            $parameters['owner_filter_disabled'] = $owner === 0 ? 1 : 0;
        }

        return [$where, $parameters];
    }

    private function orderListDefinition(string $type): array
    {
        return match ($type) {
            'sales' => [
                'order_alias' => 'so',
                'party_alias' => 'c',
                'select' => 'so.id, so.order_number, so.customer_id, so.warehouse_id, so.status, '
                    . 'so.order_date, so.created_by, so.approved_by, c.name AS customer, u.name AS creator',
                'from' => 'sales_orders AS so JOIN customers AS c ON c.id = so.customer_id '
                    . 'JOIN users AS u ON u.id = so.created_by',
                'count_from' => 'sales_orders AS so JOIN customers AS c ON c.id = so.customer_id',
                'has_owner_filter' => true,
                'sort_columns' => [
                    'order_number' => 'so.order_number',
                    'customer' => 'c.name',
                    'creator' => 'u.name',
                    'order_date' => 'so.order_date',
                    'status' => 'so.status',
                ],
            ],
            'purchase' => [
                'order_alias' => 'po',
                'party_alias' => 's',
                'select' => 'po.id, po.order_number, po.supplier_id, po.warehouse_id, po.status, '
                    . 'po.order_date, po.created_by, s.name AS supplier',
                'from' => 'purchase_orders AS po JOIN suppliers AS s ON s.id = po.supplier_id',
                'count_from' => 'purchase_orders AS po JOIN suppliers AS s ON s.id = po.supplier_id',
                'has_owner_filter' => false,
                'sort_columns' => [
                    'order_number' => 'po.order_number',
                    'supplier' => 's.name',
                    'order_date' => 'po.order_date',
                    'status' => 'po.status',
                ],
            ],
            default => throw new \InvalidArgumentException('Jenis order tidak valid.'),
        };
    }

    public function ledger(array $filters): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT
                sl.id,
                sl.product_id,
                sl.warehouse_id,
                sl.movement_type,
                sl.quantity,
                sl.reference_type,
                sl.reference_id,
                sl.performed_by,
                sl.created_at,
                sl.quantity_before,
                sl.quantity_after,
                sl.notes,
                p.sku,
                p.name AS product,
                w.name AS warehouse,
                u.name AS performer
            FROM stock_ledger AS sl
            JOIN products AS p
                ON p.id = sl.product_id
            JOIN warehouses AS w
                ON w.id = sl.warehouse_id
            JOIN users AS u
                ON u.id = sl.performed_by
            WHERE sl.created_at >= :start
              AND sl.created_at < DATE_ADD(
                    :end,
                    INTERVAL 1 DAY
                )
            ORDER BY
                sl.created_at ASC,
                sl.id ASC
        SQL);
        $statement->execute([
            'start' => $filters['start'],
            'end'   => $filters['end'],
        ]);
        return $statement->fetchAll();
    }

    public function orderReport(array $filters): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT 'PO' AS order_type, po.order_number, po.status, po.order_date,
                   s.name AS counterparty
            FROM purchase_orders po
            INNER JOIN suppliers s ON s.id = po.supplier_id
            WHERE po.order_date >= :po_start AND po.order_date <= :po_end
            UNION ALL
            SELECT 'SO' AS order_type, so.order_number, so.status, so.order_date,
                   c.name AS counterparty
            FROM sales_orders so
            INNER JOIN customers c ON c.id = so.customer_id
            WHERE so.order_date >= :so_start AND so.order_date <= :so_end
            ORDER BY order_date ASC, order_number ASC
        SQL);
        $statement->execute([
            'po_start' => $filters['start'],
            'po_end' => $filters['end'],
            'so_start' => $filters['start'],
            'so_end' => $filters['end'],
        ]);
        return $statement->fetchAll();
    }
}
