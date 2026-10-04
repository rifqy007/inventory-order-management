<?php

declare(strict_types=1);

namespace App\Support;

final class TableView
{
    public static function sortHeader(
        array $filters,
        string $column,
        string $label,
        string $path,
        bool $excludeOwner = false
    ): string {
        $active = $filters['sort'] === $column;
        $next = $filters;
        $next['sort'] = $column;
        $next['direction'] = $active && $filters['direction'] === 'asc' ? 'desc' : 'asc';
        $next['page'] = 1;
        if ($excludeOwner) {
            unset($next['owner']);
        }

        $arrow = match (true) {
            !$active => '↕',
            $filters['direction'] === 'asc' => '↑',
            default => '↓',
        };
        $ariaSort = match (true) {
            !$active => 'none',
            $filters['direction'] === 'asc' => 'ascending',
            default => 'descending',
        };
        $query = Http::e(http_build_query($next));

        $header = match ($ariaSort) {
            'ascending' => '<th aria-sort="ascending">',
            'descending' => '<th aria-sort="descending">',
            default => '<th aria-sort="none">',
        };

        return $header . '<a class="sort-link" href="'
            . Http::e($path) . '?' . $query . '"><span>' . Http::e($label)
            . '</span><span class="sort-indicator" aria-hidden="true">'
            . $arrow . '</span></a></th>';
    }
}
