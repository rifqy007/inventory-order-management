<?php

declare(strict_types=1);

namespace App\Repository;

use InvalidArgumentException;

trait MysqlInventoryMasterOperations
{
    public function all(string $table): array
    {
        $map = [
            'categories' => ['id', 'name', 'description', 'is_active'],
            'warehouses' => ['id', 'name', 'location', 'is_active'],
            'suppliers' => ['id', 'name', 'contact', 'address', 'is_active'],
            'customers' => ['id', 'name', 'contact', 'address', 'is_active'],
            'users' => ['id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at'],
            'purchase_orders' => ['id', 'order_number', 'supplier_id', 'warehouse_id', 'status', 'order_date', 'created_by'],
            'sales_orders' => ['id', 'order_number', 'customer_id', 'warehouse_id', 'status', 'order_date', 'created_by', 'approved_by'],
        ];
        if (!isset($map[$table])) {
            throw new InvalidArgumentException('Tabel tidak diizinkan.');
        }
        return $this->query->table($table)->select($map[$table])->orderBy('id', 'DESC')->limit(100)->get();
    }

    public function findMasterById(string $table, int $id): ?array
    {
        $map = ['categories' => ['id', 'name', 'description', 'is_active'], 'warehouses' => ['id', 'name', 'location', 'is_active'], 'suppliers' => ['id', 'name', 'contact', 'address', 'is_active'], 'customers' => ['id', 'name', 'contact', 'address', 'is_active']];
        if (!isset($map[$table])) {
            throw new InvalidArgumentException('Tabel tidak diizinkan.');
        }
        return $this->query->table($table)->select($map[$table])->where('id', '=', $id)->first();
    }

    public function insertMaster(string $table, array $data): void
    {
        $columns = $this->masterColumns($table);
        $selected = array_intersect_key($data, array_flip($columns));
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
        $this->pdo->prepare($sql)->execute($selected);
    }
    public function updateMaster(string $table, int $id, array $data): void
    {
        $columns = $this->masterColumns($table);
        $set = implode(',', array_map(fn($c) => $c . '=:' . $c, $columns));
        $params = array_intersect_key($data, array_flip($columns));
        $params['id'] = $id;
        $this->pdo->prepare('UPDATE ' . $table . ' SET ' . $set . ' WHERE id=:id')->execute($params);
    }
    public function setMasterActive(string $table, int $id, bool $active): void
    {
        $this->masterColumns($table);
        $this->pdo->prepare('UPDATE ' . $table . ' SET is_active=:active WHERE id=:id')->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }
    private function masterColumns(string $table): array
    {
        $map = ['categories' => ['name', 'description'], 'warehouses' => ['name', 'location'], 'suppliers' => ['name', 'contact', 'address'], 'customers' => ['name', 'contact', 'address']];
        if (!isset($map[$table])) {
            throw new InvalidArgumentException('Master tidak diizinkan.');
        }
        return $map[$table];
    }
}
