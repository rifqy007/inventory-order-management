<?php

declare(strict_types=1);

use App\Support\Http;

$totalStock = array_sum(array_map(
    static fn(array $warehouse): int => (int) $warehouse['quantity'],
    $product['warehouses']
));
$isActive = (int) $product['is_active'] === 1;
?>
<div class="product-detail-toolbar">
    <a class="button button-secondary" href="<?= Http::e($returnTo) ?>"><span aria-hidden="true">←</span> Kembali ke produk</a>
    <?php if (($user['role'] ?? '') === 'Admin'): ?>
        <a class="button" href="/products/<?= (int) $product['id'] ?>/edit?return_to=<?= rawurlencode($returnTo) ?>">Edit produk</a>
    <?php endif; ?>
</div>

<header class="product-detail-heading">
    <div>
        <span class="eyebrow">KATALOG PRODUK</span>
        <h1><?= Http::e($product['name']) ?></h1>
        <p><span class="product-sku"><?= Http::e($product['sku']) ?></span><span aria-hidden="true">·</span><?= Http::e($product['category']) ?></p>
    </div>
    <span class="status-badge <?= $isActive ? 'status-active' : 'status-inactive' ?>"><?= $isActive ? 'Aktif' : 'Nonaktif' ?></span>
</header>

<section class="product-detail-card" aria-label="Informasi produk">
    <div class="product-detail-visual">
        <?php if (!empty($product['image_path'])): ?>
            <img src="<?= Http::e($product['image_path']) ?>" alt="Foto <?= Http::e($product['name']) ?>">
        <?php else: ?>
            <div class="product-detail-placeholder"><span aria-hidden="true"><?= Http::e(strtoupper(substr((string) $product['name'], 0, 1))) ?></span><small>Belum ada foto produk</small></div>
        <?php endif; ?>
    </div>
    <div class="product-detail-content">
        <div class="product-detail-section-heading">
            <div><span class="eyebrow">RINGKASAN</span><h2>Informasi produk</h2></div>
            <span class="product-stock-total"><strong><?= number_format($totalStock, 0, ',', '.') ?></strong><small>Total stok</small></span>
        </div>
        <dl class="product-spec-grid">
            <div class="product-spec"><dt>Satuan</dt><dd><?= Http::e($product['unit']) ?></dd></div>
            <div class="product-spec"><dt>Reorder point</dt><dd><?= number_format((int) $product['reorder_point'], 0, ',', '.') ?> <small><?= Http::e($product['unit']) ?></small></dd></div>
            <div class="product-spec product-spec-price"><dt>Harga beli</dt><dd>Rp <?= number_format((float) $product['purchase_price'], 0, ',', '.') ?></dd></div>
            <div class="product-spec product-spec-price"><dt>Harga jual</dt><dd>Rp <?= number_format((float) $product['selling_price'], 0, ',', '.') ?></dd></div>
        </dl>
    </div>
</section>

<section class="product-warehouse-section">
    <div class="product-detail-section-heading">
        <div><span class="eyebrow">DISTRIBUSI STOK</span><h2>Stok per gudang</h2><p>Rincian jumlah produk yang tercatat pada setiap gudang.</p></div>
        <span class="warehouse-count"><?= count($product['warehouses']) ?> gudang</span>
    </div>
    <div class="table responsive-table">
        <table>
            <thead><tr><th>Gudang</th><th>Jumlah tersedia</th></tr></thead>
            <tbody>
                <?php if ($product['warehouses'] === []): ?>
                    <tr><td colspan="2" class="empty-state">Belum ada catatan stok di gudang.</td></tr>
                <?php else: ?>
                    <?php foreach ($product['warehouses'] as $warehouse): ?>
                        <tr><td><?= Http::e($warehouse['name']) ?></td><td><span class="warehouse-quantity"><?= number_format((int) $warehouse['quantity'], 0, ',', '.') ?> <?= Http::e($product['unit']) ?></span></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
