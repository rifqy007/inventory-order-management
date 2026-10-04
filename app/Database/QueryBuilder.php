<?php

declare(strict_types=1);

namespace App\Database;

use InvalidArgumentException;
use PDO;

final class QueryBuilder
{
    private string $table = '';
    private string $alias = '';
    private array $columns = [];
    private array $joins = [];
    private array $conditions = [];
    private array $parameters = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private bool $forUpdate = false;
    private int $parameterIndex = 0;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function table(string $table, string $alias = ''): self
    {
        $clone = clone $this;
        $clone->reset();
        $clone->table = $clone->identifier($table);
        $clone->alias = $alias === '' ? '' : $clone->identifier($alias);
        return $clone;
    }

    public function select(array $columns): self
    {
        if ($columns === []) {
            throw new InvalidArgumentException('Kolom SELECT wajib ditentukan.');
        }
        foreach ($columns as $column) {
            if ($column === '*' || str_ends_with($column, '.*')) {
                throw new InvalidArgumentException('SELECT wildcard tidak diizinkan.');
            }
        }
        $this->columns = array_map([$this, 'selectExpression'], $columns);
        return $this;
    }

    public function join(string $table, string $alias, string $left, string $operator, string $right): self
    {
        if (!in_array($operator, ['=', '<>', '>', '>=', '<', '<='], true)) {
            throw new InvalidArgumentException('Operator JOIN tidak valid.');
        }
        $this->joins[] = sprintf(
            'INNER JOIN %s AS %s ON %s %s %s',
            $this->identifier($table),
            $this->identifier($alias),
            $this->identifier($left),
            $operator,
            $this->identifier($right)
        );
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        if (!in_array($operator, ['=', '<>', '>', '>=', '<', '<=', 'LIKE'], true)) {
            throw new InvalidArgumentException('Operator WHERE tidak valid.');
        }
        $parameter = ':p' . ++$this->parameterIndex;
        $this->conditions[] = $this->identifier($column) . " {$operator} {$parameter}";
        $this->parameters[$parameter] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Arah sorting tidak valid.');
        }
        $this->orders[] = $this->identifier($column) . ' ' . $direction;
        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Limit harus 1 sampai 100.');
        }
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset tidak boleh negatif.');
        }
        $this->offset = $offset;
        return $this;
    }

    public function forUpdate(): self
    {
        $this->forUpdate = true;
        return $this;
    }

    public function get(): array
    {
        $statement = $this->pdo->prepare($this->toSql());
        $statement->execute($this->parameters);
        return $statement->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function toSql(): string
    {
        if ($this->table === '' || $this->columns === []) {
            throw new InvalidArgumentException('Query belum lengkap.');
        }
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table;
        if ($this->alias !== '') {
            $sql .= ' AS ' . $this->alias;
        }
        if ($this->joins !== []) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if ($this->conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        }
        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        if ($this->forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        return $sql;
    }

    private function reset(): void
    {
        $this->columns = $this->joins = $this->conditions = $this->parameters = $this->orders = [];
        $this->limit = $this->offset = null;
        $this->forUpdate = false;
        $this->parameterIndex = 0;
    }

    private function identifier(string $identifier): string
    {
        if (!preg_match('/^[A-Z_]\w*(?:\.\w+)*$/i', $identifier)) {
            throw new InvalidArgumentException('Identifier query tidak valid.');
        }
        return $identifier;
    }

    private function selectExpression(string $expression): string
    {
        if (preg_match('/^([A-Z_]\w*(?:\.\w+)*) AS ([A-Z_]\w*)$/i', $expression, $m)) {
            return $this->identifier($m[1]) . ' AS ' . $this->identifier($m[2]);
        }
        return $this->identifier($expression);
    }
}
