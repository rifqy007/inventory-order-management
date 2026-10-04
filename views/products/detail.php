<?php
declare(strict_types=1);
use App\Support\Http;
?>
<section class="page-heading"><h1><?= Http::e($product['name']) ?></h1><p><?= Http::e($product['sku']) ?> &middot; <?= Http::e($product['category']) ?></p></section>
<section class="panel">
    <?php if (!empty($product['image_path'])): ?><img src="<?= Http::e($product['image_path']) ?>" alt="Gambar <?= Http::e($product['name']) ?>" width="240"><?php endif; ?>
    <dl><dt>Unit</dt><dd><?= Http::e($product['unit']) ?></dd><dt>Harga beli</dt><dd><?= number_format((float) $product['purchase_price'], 0, ',', '.') ?></dd><dt>Harga jual</dt><dd><?= number_format((float) $product['selling_price'], 0, ',', '.') ?></dd><dt>Reorder point</dt><dd><?= (int) $product['reorder_point'] ?></dd><dt>Status</dt><dd><?= (int) $product['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></dd></dl>
</section>
<h2>Stok per gudang</h2>
<div class="table responsive-table"><table><thead><tr><th>Gudang</th><th>Jumlah</th></tr></thead><tbody>
<?php if ($product['warehouses'] === []): ?><tr><td colspan="2" class="empty-state">Belum ada catatan stok di gudang.</td></tr><?php else: foreach ($product['warehouses'] as $warehouse): ?><tr><td><?= Http::e($warehouse['name']) ?></td><td><?= (int) $warehouse['quantity'] ?></td></tr><?php endforeach; endif; ?>
</tbody></table></div>
<a class="button button-secondary" href="/products">Kembali ke produk</a>
<?php if (($user['role'] ?? '') === 'Admin'): ?> <a class="button" href="/products/<?= (int) $product['id'] ?>/edit">Edit</a><?php endif; ?>
