<?php

declare(strict_types=1);

use App\Support\Http;

$path = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$active = static fn(string $p): string => $path === $p || str_starts_with($path, $p . '/') ? ' active' : '';
$role = (string)($user['role'] ?? 'Guest');
$pageTitles = [
    'dashboard' => 'Dashboard',
    'products/index' => 'Produk & Stok',
    'products/form' => 'Data Produk',
    'products/detail' => 'Detail Produk',
    'workflow/sales-list' => 'Sales Order',
    'workflow/sales-create' => 'Buat Sales Order',
    'workflow/sales-detail' => 'Detail Sales Order',
    'workflow/purchase-list' => 'Purchase Order',
    'workflow/purchase-create' => 'Buat Purchase Order',
    'workflow/purchase-detail' => 'Detail Purchase Order',
    'workflow/ledger' => 'Stock Ledger',
    'inventory/move' => 'Mutasi Stok',
    'master/index' => 'Data Master',
    'master/edit' => 'Edit Data Master',
    'admin/users-index' => 'Manajemen User',
    'admin/users-form' => 'Data User',
];
$pageTitle = $pageTitles[$name] ?? ucwords(str_replace(['/', '-', '_'], ' ', (string) $name)); ?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Inventory &amp; Order Management</title>
    <link rel="stylesheet" href="/assets/app.css?v=20261004-5">
</head>

<body><?php if ($user !== null): ?><div class="app-shell">
            <aside class="sidebar" id="sidebar"><a class="brand" href="/dashboard"><span class="brand-mark">IO</span><span><b>Inventory</b><small>Order Management</small></span></a>
                <nav><span class="section">Overview</span><a class="nav-link<?= $active('/dashboard') ?>" href="/dashboard">Dashboard</a><span class="section">Inventory</span><a class="nav-link<?= $active('/products') ?>" href="/products">Produk &amp; Stok</a><a class="nav-link<?= $active('/stock-ledger') ?>" href="/stock-ledger">Stock Ledger</a><?php if (in_array($role, ['Admin', 'WarehouseStaff'], true)): ?><span class="section">Purchasing</span><a class="nav-link<?= $active('/purchase-orders') ?>" href="/purchase-orders/workflow">Purchase Order</a><?php endif; ?><span class="section">Sales</span><a class="nav-link<?= $active('/sales-orders') ?>" href="/sales-orders/workflow">Sales Order</a><?php if ($role === 'Admin'): ?><span class="section">Master Data</span><a class="nav-link<?= $active('/categories') ?>" href="/categories">Kategori</a><a class="nav-link<?= $active('/warehouses') ?>" href="/warehouses">Gudang</a><a class="nav-link<?= $active('/suppliers') ?>" href="/suppliers">Supplier</a><a class="nav-link<?= $active('/customers') ?>" href="/customers">Customer</a><a class="nav-link<?= $active('/admin/users') ?>" href="/admin/users">Manajemen User</a><?php endif; ?></nav>
                <div class="account"><span class="avatar"><?= Http::e(strtoupper(substr((string)$user['name'], 0, 1))) ?></span><span><b><?= Http::e($user['name']) ?></b><small><?= Http::e($role) ?></small></span></div>
            </aside>
            <div class="backdrop" id="backdrop"></div>
            <div class="app-area">
                <header class="topbar"><button id="menu" class="menu" type="button" aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu navigasi">☰</button>
                    <div class="topbar-title"><small>Inventory &amp; Order Management</small><strong><?= Http::e($pageTitle) ?></strong></div>
                    <div class="top-actions"><span class="role"><?= Http::e($role) ?></span>
                        <form method="post" action="/logout"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button class="secondary">Logout</button></form>
                    </div>
                </header>
                <main class="main"><?php if ($flash !== null): ?><div class="flash <?= Http::e($flash['type']) ?>"><?= Http::e($flash['message']) ?></div><?php endif; ?><?php require_once __DIR__ . '/' . $name . '.php'; ?></main>
            </div>
        </div><?php else: ?><main class="guest-main"><?php require_once __DIR__ . '/' . $name . '.php'; ?></main><?php endif; ?><script src="/assets/app.js?v=20261004"></script>
</body>

</html>
