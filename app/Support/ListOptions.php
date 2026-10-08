<?php

declare(strict_types=1);

namespace App\Support;

final readonly class ListOptions
{
    private const ALLOWED_PAGE_SIZES = [10, 25, 50, 100];
    private const ALLOWED_SORTS = ['asc', 'desc'];

    public function __construct(
        public int $page,
        public int $perPage,
        public string $direction,
        public string $sort
    ) {
    }

    public static function fromRequest(array $query, array $allowedSorts = [], string $defaultSort = ''): self
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $requestedSize = (int) ($query['per_page'] ?? 10);
        $perPage = in_array($requestedSize, self::ALLOWED_PAGE_SIZES, true)
            ? $requestedSize
            : 10;
        $requestedSort = strtolower((string) ($query['direction'] ?? 'asc'));
        $direction = in_array($requestedSort, self::ALLOWED_SORTS, true)
            ? $requestedSort
            : 'asc';
        $requestedColumn = (string) ($query['sort'] ?? $defaultSort);
        $sort = in_array($requestedColumn, $allowedSorts, true) ? $requestedColumn : $defaultSort;
        return new self($page, $perPage, $direction, $sort);
    }

    public function sqlDirection(): string
    {
        return $this->direction === 'asc' ? 'ASC' : 'DESC';
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
