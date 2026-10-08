<?php

declare(strict_types=1);

use App\Support\Http;

$welcomePrefix = 'Selamat datang, ';
$productsPath = '/products';
$salesWorkflowPath = '/sales-orders/workflow';
$purchaseWorkflowPath = '/purchase-orders/workflow';
$approvedSalesPath = $salesWorkflowPath . '?status=Approved';
$pendingSalesPath = $salesWorkflowPath . '?status=PendingApproval';

$role = (string) ($user['role'] ?? '');
$roleContent = match ($role) {
    'Sales' => [
        'eyebrow' => 'RUANG KERJA SALES',
        'title' => $welcomePrefix . $user['name'],
        'description' => 'Pantau pesanan penjualan Anda dan lanjutkan pekerjaan yang masih berjalan.',
        'primary' => ['label' => '＋ Buat Sales Order', 'href' => '/sales-orders/workflow/new'],
        'secondary' => ['label' => 'Lihat pesanan saya', 'href' => $salesWorkflowPath],
        'actions' => [
            ['icon' => '＋', 'title' => 'Buat Sales Order', 'description' => 'Mulai pesanan penjualan baru.', 'href' => '/sales-orders/workflow/new', 'tone' => 'blue'],
            ['icon' => 'SO', 'title' => 'Pesanan saya', 'description' => 'Lihat status dan detail pesanan.', 'href' => $salesWorkflowPath, 'tone' => 'purple'],
            ['icon' => 'ST', 'title' => 'Produk & Stok', 'description' => 'Periksa produk sebelum membuat pesanan.', 'href' => $productsPath, 'tone' => 'teal'],
            ['icon' => '↗', 'title' => 'Riwayat stok', 'description' => 'Telusuri pergerakan barang.', 'href' => '/stock-ledger', 'tone' => 'orange'],
        ],
    ],
    'WarehouseStaff' => [
        'eyebrow' => 'OPERASIONAL GUDANG',
        'title' => $welcomePrefix . $user['name'],
        'description' => 'Kelola penerimaan, pengeluaran, dan kondisi stok di gudang.',
        'primary' => ['label' => '＋ Catat pergerakan stok', 'href' => '/inventory/move'],
        'secondary' => ['label' => 'Lihat daftar pesanan', 'href' => $purchaseWorkflowPath],
        'actions' => [
            ['icon' => 'PO', 'title' => 'Penerimaan barang', 'description' => 'Lihat Purchase Order yang perlu diterima.', 'href' => $purchaseWorkflowPath, 'tone' => 'blue'],
            ['icon' => 'SO', 'title' => 'Pengeluaran barang', 'description' => 'Proses Sales Order yang disetujui.', 'href' => $approvedSalesPath, 'tone' => 'purple'],
            ['icon' => '!', 'title' => 'Stok menipis', 'description' => 'Tinjau produk di bawah batas stok.', 'href' => $productsPath . '?stock=low', 'tone' => 'orange'],
            ['icon' => '＋', 'title' => 'Pergerakan stok', 'description' => 'Catat penerimaan atau penyesuaian manual.', 'href' => '/inventory/move', 'tone' => 'teal'],
        ],
    ],
    default => [
        'eyebrow' => 'DASHBOARD ADMIN',
        'title' => $welcomePrefix . $user['name'],
        'description' => 'Pantau inventaris, pesanan, dan pekerjaan operasional dari satu tempat.',
        'primary' => ['label' => 'Tinjau approval SO', 'href' => $pendingSalesPath],
        'secondary' => ['label' => '＋ Tambah produk', 'href' => $productsPath . '/new'],
        'actions' => [
            ['icon' => 'ST', 'title' => 'Produk & Stok', 'description' => 'Kelola katalog dan pantau stok gudang.', 'href' => $productsPath, 'tone' => 'blue'],
            ['icon' => 'SO', 'title' => 'Persetujuan Sales Order', 'description' => 'Tinjau pesanan yang menunggu approval.', 'href' => $pendingSalesPath, 'tone' => 'purple'],
            ['icon' => 'PO', 'title' => 'Purchase Order', 'description' => 'Pantau pesanan dan penerimaan barang.', 'href' => $purchaseWorkflowPath, 'tone' => 'teal'],
            ['icon' => 'US', 'title' => 'Manajemen User', 'description' => 'Atur akun dan role pengguna.', 'href' => '/admin/users', 'tone' => 'orange'],
        ],
    ],
};

$metricInfo = [
    'Nilai inventori' => ['icon' => 'Rp', 'note' => 'Σ (stok tiap gudang × harga beli produk)', 'href' => $productsPath, 'tone' => 'blue', 'money' => true],
    'Produk aktif' => ['icon' => 'ST', 'note' => 'Produk tersedia di katalog', 'href' => $productsPath . '?active=1', 'tone' => 'teal'],
    'Low stock' => ['icon' => '!', 'note' => 'Produk aktif di bawah reorder point', 'href' => $productsPath . '?stock=low&active=1', 'tone' => 'orange'],
    'SO menunggu approval' => ['icon' => 'SO', 'note' => 'Menunggu tindakan Admin', 'href' => $pendingSalesPath, 'tone' => 'purple'],
    'PO menunggu penerimaan' => ['icon' => 'PO', 'note' => 'Pesanan siap diproses gudang', 'href' => $purchaseWorkflowPath, 'tone' => 'blue'],
    'Order saya' => ['icon' => 'SO', 'note' => 'Total pesanan yang Anda buat', 'href' => $salesWorkflowPath, 'tone' => 'blue'],
    'Draft' => ['icon' => 'DR', 'note' => 'Pesanan yang masih berupa draft', 'href' => $salesWorkflowPath . '?status=Draft', 'tone' => 'teal'],
    'Pending approval' => ['icon' => '⏱', 'note' => 'Menunggu keputusan Admin', 'href' => $pendingSalesPath, 'tone' => 'orange'],
    'Approved' => ['icon' => '✓', 'note' => 'Siap diproses oleh gudang', 'href' => $approvedSalesPath, 'tone' => 'purple'],
    'Fulfilled' => ['icon' => '✓', 'note' => 'Pesanan selesai diproses', 'href' => $salesWorkflowPath . '?status=Fulfilled', 'tone' => 'teal'],
    'Cancelled' => ['icon' => '×', 'note' => 'Pesanan yang dibatalkan', 'href' => $salesWorkflowPath . '?status=Cancelled', 'tone' => 'orange'],
    'Goods receipt' => ['icon' => 'PO', 'note' => 'Purchase Order menunggu penerimaan', 'href' => $purchaseWorkflowPath, 'tone' => 'blue'],
    'Goods issue' => ['icon' => 'SO', 'note' => 'Sales Order siap dikeluarkan', 'href' => $approvedSalesPath, 'tone' => 'purple'],
    'Movement hari ini' => ['icon' => '↗', 'note' => 'Mutasi stok tercatat hari ini', 'href' => '/stock-ledger', 'tone' => 'teal'],
];
$metricLabels = [
    'Nilai inventori' => 'Nilai inventaris',
    'Low stock' => 'Stok menipis',
    'SO menunggu approval' => 'Pesanan penjualan menunggu persetujuan',
    'PO menunggu penerimaan' => 'Pesanan pembelian menunggu penerimaan',
    'Order saya' => 'Pesanan saya',
    'Draft' => 'Draf',
    'Pending approval' => 'Menunggu persetujuan',
    'Approved' => 'Disetujui',
    'Fulfilled' => 'Selesai',
    'Cancelled' => 'Dibatalkan',
    'Goods receipt' => 'Penerimaan barang',
    'Goods issue' => 'Pengeluaran barang',
    'Movement hari ini' => 'Mutasi stok hari ini',
];
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-content">
        <span class="dashboard-eyebrow"><?= Http::e($roleContent['eyebrow']) ?></span>
        <h1><?= Http::e($roleContent['title']) ?></h1>
        <p><?= Http::e($roleContent['description']) ?></p>
        <div class="dashboard-hero-actions">
            <a class="button dashboard-primary" href="<?= Http::e($roleContent['primary']['href']) ?>"><?= Http::e($roleContent['primary']['label']) ?></a>
            <a class="dashboard-secondary" href="<?= Http::e($roleContent['secondary']['href']) ?>"><?= Http::e($roleContent['secondary']['label']) ?> <span aria-hidden="true">→</span></a>
        </div>
    </div>
    <div class="dashboard-hero-art" aria-hidden="true"><span class="hero-orbit orbit-one"></span><span class="hero-orbit orbit-two"></span><div class="hero-art-card"><span class="hero-art-icon">IO</span><span><b>Inventory</b><small>Order Management</small></span><strong>↗</strong></div><span class="hero-art-dot dot-one"></span><span class="hero-art-dot dot-two"></span></div>
</section>

<section class="dashboard-section">
    <div class="dashboard-section-heading"><div><span class="eyebrow">RINGKASAN</span><h2>Ringkasan operasional</h2></div><span class="dashboard-section-caption"><?= Http::e($role) ?> · Data terkini</span></div>
    <div class="metric-grid">
        <?php foreach ($metrics as $label => $value): $info = $metricInfo[$label] ?? ['icon' => '•', 'note' => 'Ringkasan operasional', 'href' => '/dashboard', 'tone' => 'blue']; ?>
            <a class="metric-card metric-<?= Http::e($info['tone']) ?>" href="<?= Http::e($info['href']) ?>">
                <span class="metric-icon" aria-hidden="true"><?= Http::e($info['icon']) ?></span>
                <span class="metric-label"><?= Http::e($metricLabels[$label] ?? $label) ?></span>
                <strong class="metric-value"><?php if (!empty($info['money'])): ?>Rp <?= number_format((float) $value, 0, ',', '.') ?><?php else: ?><?= number_format((float) $value, 0, ',', '.') ?><?php endif; ?></strong>
                <span class="metric-note"><?= Http::e($info['note']) ?></span>
                <span class="metric-arrow" aria-hidden="true">↗</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="dashboard-shortcuts-section">
    <div class="dashboard-section-heading"><div><span class="eyebrow">PINTASAN</span><h2>Akses cepat</h2></div><span class="dashboard-section-caption">Pintasan untuk pekerjaan <?= Http::e(strtolower($role)) ?></span></div>
    <div class="dashboard-shortcuts">
        <?php foreach ($roleContent['actions'] as $action): ?>
            <a class="dashboard-shortcut shortcut-<?= Http::e($action['tone']) ?>" href="<?= Http::e($action['href']) ?>">
                <span class="shortcut-icon" aria-hidden="true"><?= Http::e($action['icon']) ?></span>
                <span class="shortcut-copy"><strong><?= Http::e($action['title']) ?></strong><small><?= Http::e($action['description']) ?></small></span>
                <span class="shortcut-arrow" aria-hidden="true">→</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
