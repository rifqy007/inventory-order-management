<?php

declare(strict_types=1);

/**
 * JOB-01: tampilkan stok produk yang berada di bawah reorder point.
 *
 * Jalankan dengan:
 * docker compose exec app php scripts/check-low-stock.php
 */

try {
    $container = require dirname(__DIR__) . '/config/bootstrap.php';

    $sql = '
        SELECT
            p.sku,
            p.name,
            w.name AS warehouse,
            ps.quantity,
            p.reorder_point
        FROM product_stocks AS ps
        JOIN products AS p ON p.id = ps.product_id
        JOIN warehouses AS w ON w.id = ps.warehouse_id
        WHERE ps.quantity <= p.reorder_point
        ORDER BY p.sku, w.name
    ';

    $rows = $container['repo']->pdo()->query($sql)->fetchAll();

    if ($rows === []) {
        echo "Tidak ada stok produk yang mencapai reorder point.\n";
        exit(0);
    }

    printf("Produk dengan stok rendah: %d\n", count($rows));

    foreach ($rows as $row) {
        printf(
            "%s | %s | %s | stok=%d | reorder=%d\n",
            $row['sku'],
            $row['name'],
            $row['warehouse'],
            $row['quantity'],
            $row['reorder_point']
        );
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        '[ERROR] Pemeriksaan stok rendah gagal: '
            . $exception->getMessage()
            . PHP_EOL
    );
    exit(1);
}
