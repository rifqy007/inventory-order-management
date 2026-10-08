<?php

declare(strict_types=1);

namespace App\Repository;

use InvalidArgumentException;
use App\Support\ListOptions;

trait MysqlInventoryMasterOperations
{
    private const INVALID_MASTER_TABLE_MESSAGE = 'Master tidak diizinkan.';

    private const MASTER_LIST_COLUMNS = [
        'categories' => ['name' => 'name', 'description' => 'description', 'is_active' => 'is_active'],
        'warehouses' => ['name' => 'name', 'location' => 'location', 'is_active' => 'is_active'],
        'suppliers' => ['name' => 's.name', 'category' => 'c.name', 'contact' => 's.contact', 'address' => 's.address', 'is_active' => 's.is_active'],
        'customers' => ['name' => 'name', 'contact' => 'contact', 'address' => 'address', 'is_active' => 'is_active'],
    ];

    public function masterRows(string $table, array $filters): array
    {
        $columns = self::MASTER_LIST_COLUMNS[$table] ?? throw new InvalidArgumentException(self::INVALID_MASTER_TABLE_MESSAGE);
        $sort = $filters['sort'] ?? 'name';
        $sortColumn = $columns[$sort] ?? 'name';
        $direction = strtoupper((string) ($filters['direction'] ?? 'asc')) === 'DESC' ? 'DESC' : 'ASC';
        $searchColumns = array_values(array_filter(
            $columns,
            static fn(string $key): bool => $key !== 'is_active',
            ARRAY_FILTER_USE_KEY
        ));
        $search = trim((string) ($filters['q'] ?? ''));
        $where = '';
        $parameters = [];
        if ($search !== '') {
            $conditions = [];
            foreach ($searchColumns as $index => $column) {
                $parameter = 'search_' . $index;
                $conditions[] = $column . ' LIKE :' . $parameter;
                $parameters[$parameter] = '%' . $search . '%';
            }
            $where = ' WHERE (' . implode(' OR ', $conditions) . ')';
        }

        $limit = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $offset = max(0, ((int) ($filters['page'] ?? 1) - 1) * $limit);
        $selectColumns = array_map(
            static fn(string $key, string $column): string => $column === $key ? $column : $column . ' AS ' . $key,
            array_keys($columns),
            array_values($columns)
        );
        $from = $table === 'suppliers'
            ? 'suppliers AS s JOIN categories AS c ON c.id = s.category_id'
            : $table;
        $idColumn = $table === 'suppliers' ? 's.id' : 'id';
        $sql = 'SELECT ' . $idColumn . ' AS id, ' . implode(', ', $selectColumns) . ' FROM ' . $from
            . $where . ' ORDER BY ' . $sortColumn . ' ' . $direction . ', ' . $idColumn . ' ASC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function masterCount(string $table, string $search): int
    {
        $columns = self::MASTER_LIST_COLUMNS[$table] ?? throw new InvalidArgumentException(self::INVALID_MASTER_TABLE_MESSAGE);
        $searchColumns = array_values(array_filter(
            $columns,
            static fn(string $key): bool => $key !== 'is_active',
            ARRAY_FILTER_USE_KEY
        ));
        $search = trim($search);
        $from = $table === 'suppliers'
            ? 'suppliers AS s JOIN categories AS c ON c.id = s.category_id'
            : $table;
        if ($search === '') {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM ' . $from)->fetchColumn();
        }

        $conditions = [];
        $parameters = [];
        foreach ($searchColumns as $index => $column) {
            $parameter = 'search_' . $index;
            $conditions[] = $column . ' LIKE :' . $parameter;
            $parameters[$parameter] = '%' . $search . '%';
        }
        $where = implode(' OR ', $conditions);
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $from . ' WHERE ' . $where);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function all(string $table): array
    {
        $map = [
            'categories' => ['id', 'name', 'description', 'is_active'],
            'warehouses' => ['id', 'name', 'location', 'is_active'],
            'suppliers' => ['id', 'name', 'category_id', 'contact', 'address', 'is_active'],
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
        if ($table === 'suppliers') {
            $statement = $this->pdo->prepare(
                'SELECT s.id, s.name, s.category_id, c.name AS category_name, s.contact, s.address, s.is_active
                 FROM suppliers AS s JOIN categories AS c ON c.id = s.category_id WHERE s.id = :id'
            );
            $statement->execute(['id' => $id]);
            return $statement->fetch() ?: null;
        }
        $map = ['categories' => ['id', 'name', 'description', 'is_active'], 'warehouses' => ['id', 'name', 'location', 'is_active'], 'customers' => ['id', 'name', 'contact', 'address', 'is_active']];
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
        $map = ['categories' => ['name', 'description'], 'warehouses' => ['name', 'location'], 'suppliers' => ['name', 'category_id', 'contact', 'address'], 'customers' => ['name', 'contact', 'address']];
        if (!isset($map[$table])) {
            throw new InvalidArgumentException(self::INVALID_MASTER_TABLE_MESSAGE);
        }
        return $map[$table];
    }
}
