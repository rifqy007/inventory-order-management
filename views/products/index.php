<?php

declare(strict_types=1);

use App\Support\Http;
use App\Support\RoutePath;
use App\Support\TableView;

$pages = max(1, (int) ceil($total / $filters['per_page']));
$query = $filters;
$returnTo = RoutePath::PRODUCTS . '?' . http_build_query($filters);


?>
<header class="page-heading">
    <div><span class="eyebrow">INVENTORY</span><h1>Produk &amp; Stok</h1><p>Kelola produk dan pantau ketersediaan di setiap gudang.</p></div>
    <?php if (($user['role'] ?? '') === 'Admin') :
        ?><a class="button" href="<?= RoutePath::PRODUCTS ?>/new?return_to=<?= rawurlencode($returnTo) ?>">＋ Tambah Produk</a><?php
    endif; ?>
</header>
<details class="filter-disclosure" <?= ($filters['q'] !== '' || $filters['category_id'] > 0 || $filters['stock'] !== '' || $filters['active'] !== '') ? 'open' : '' ?>>
    <summary><span class="filter-summary-icon">⌕</span><span><strong>Cari dan filter produk</strong><small>Nama, SKU, kategori, dan status stok</small></span><span class="summary-chevron">⌄</span></summary>
    <form class="filter-content" method="get" action="<?= RoutePath::PRODUCTS ?>">
        <div class="filter-fields">
            <label for="q">Nama produk atau SKU<input id="q" name="q" value="<?= Http::e($filters['q']) ?>" placeholder="Contoh: SKU-001 atau Mouse" autocomplete="off"></label>
            <label for="category_id">Kategori<select id="category_id" name="category_id"><option value="0">Semua kategori</option><?php foreach ($categories as $category) :
                ?><option value="<?= (int) $category['id'] ?>" <?= $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= Http::e($category['name']) ?></option><?php
                                                                                                                                  endforeach; ?></select></label>
            <label for="stock">Status stok<select id="stock" name="stock"><option value="">Semua status</option><option value="low" <?= $filters['stock'] === 'low' ? 'selected' : '' ?>>Stok menipis</option><option value="normal" <?= $filters['stock'] === 'normal' ? 'selected' : '' ?>>Stok normal</option></select></label>
            <label for="active">Status produk<select id="active" name="active"><option value="">Semua produk</option><option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Aktif</option><option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Nonaktif</option></select></label>
        </div>
        <input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>"><input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>"><input type="hidden" name="per_page" value="<?= (int) $filters['per_page'] ?>">
        <div class="filter-actions"><input type="hidden" name="page" value="1"><button type="submit">Terapkan pencarian</button><a class="button button-secondary" href="<?= RoutePath::PRODUCTS ?>">Reset</a></div>
    </form>
</details>
<p><?= (int) $total ?> produk ditemukan.</p>
<div class="table responsive-table">
    <table>
        <thead><tr><?= TableView::sortHeader($filters, 'sku', 'SKU', RoutePath::PRODUCTS) ?><?= TableView::sortHeader($filters, 'name', 'Nama', RoutePath::PRODUCTS) ?><?= TableView::sortHeader($filters, 'category', 'Kategori', RoutePath::PRODUCTS) ?><?= TableView::sortHeader($filters, 'unit', 'Unit', RoutePath::PRODUCTS) ?><?= TableView::sortHeader($filters, 'selling_price', 'Harga Jual', RoutePath::PRODUCTS) ?><?= TableView::sortHeader($filters, 'total_stock', 'Stok Total', RoutePath::PRODUCTS) ?><?= TableView::sortHeader($filters, 'warehouse_stocks', 'Rincian Gudang', RoutePath::PRODUCTS) ?><?= TableView::sortHeader($filters, 'reorder_point', 'Reorder Point', RoutePath::PRODUCTS) ?><?php if (($user['role'] ?? '') === 'Admin') :
            ?><th>Aksi</th><?php
                   endif; ?></tr></thead>
        <tbody>
        <?php if ($products === []) : ?>
            <tr><td colspan="<?= ($user['role'] ?? '') === 'Admin' ? 9 : 8 ?>" class="empty-state">Tidak ada produk yang cocok dengan filter ini.</td></tr>
        <?php else :
            foreach ($products as $product) : ?>
            <tr>
                <td><?= Http::e($product['sku']) ?></td><td><div class="product-row-identity"><?php if (!empty($product['image_path'])) :
                    ?><img class="product-thumbnail" src="<?= Http::e($product['image_path']) ?>" alt="" loading="lazy" width="44" height="44"><?php
                    else: ?><span class="product-thumbnail product-thumbnail-placeholder" aria-hidden="true"><?= Http::e(strtoupper(substr((string) $product['name'], 0, 1))) ?></span><?php
                    endif; ?><a href="<?= RoutePath::PRODUCTS ?>/<?= (int) $product['id'] ?>?return_to=<?= rawurlencode($returnTo) ?>"><?= Http::e($product['name']) ?></a></div></td>
                <td><?= Http::e($product['category']) ?></td><td><?= Http::e($product['unit']) ?></td>
                <td><?= number_format((float) $product['selling_price'], 0, ',', '.') ?></td>
                <td><?= (int) $product['total_stock'] ?></td><td><?= Http::e($product['warehouse_stocks'] ?? 'Belum ada stok') ?></td><td><?= (int) $product['reorder_point'] ?></td>
                <?php if (($user['role'] ?? '') === 'Admin') :
                    ?><td><div class="action-group"><a class="button button-secondary" href="<?= RoutePath::PRODUCTS ?>/<?= (int) $product['id'] ?>/edit?return_to=<?= rawurlencode($returnTo) ?>">Edit</a><form method="post" action="<?= RoutePath::PRODUCTS ?>/<?= (int) $product['id'] ?>/status"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><input type="hidden" name="active" value="<?= (int) $product['is_active'] === 1 ? '0' : '1' ?>"><input type="hidden" name="return_to" value="<?= Http::e($returnTo) ?>"><button class="<?= (int) $product['is_active'] === 1 ? 'button-danger' : 'button-success' ?>" type="submit"><?= (int) $product['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></div></td><?php
                endif; ?>
            </tr>
            <?php endforeach;
        endif; ?>
        </tbody>
    </table>
</div>
<div class="table-controls"><form class="table-footer" method="get" action="<?= RoutePath::PRODUCTS ?>">
    <label for="rows-per-page">Baris per halaman<select id="rows-per-page" name="per_page"><?php foreach ([10, 25, 50, 100] as $size) :
        ?><option value="<?= $size ?>" <?= $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php
                                                                                           endforeach; ?></select></label>
    <input type="hidden" name="q" value="<?= Http::e($filters['q']) ?>"><input type="hidden" name="category_id" value="<?= (int) $filters['category_id'] ?>"><input type="hidden" name="stock" value="<?= Http::e($filters['stock']) ?>"><input type="hidden" name="active" value="<?= Http::e($filters['active']) ?>"><input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>"><input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>"><input type="hidden" name="page" value="1">
    <button class="secondary" type="submit">Terapkan</button>
</form>
<nav class="pagination" aria-label="Navigasi halaman produk">
    <?php if ($filters['page'] > 1) :
        $query['page'] = $filters['page'] - 1; ?>
        <a href="<?= RoutePath::PRODUCTS ?>?<?= Http::e(http_build_query($query)) ?>">Sebelumnya</a>
    <?php endif; ?>
    <span>Halaman <?= (int) $filters['page'] ?> dari <?= $pages ?></span>
    <?php if ($filters['page'] < $pages) :
        $query['page'] = $filters['page'] + 1; ?>
        <a href="<?= RoutePath::PRODUCTS ?>?<?= Http::e(http_build_query($query)) ?>">Berikutnya</a>
    <?php endif; ?>
</nav></div>
