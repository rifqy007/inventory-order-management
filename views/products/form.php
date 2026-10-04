<?php
declare(strict_types=1);
use App\Support\Http;
$editing = $product !== null;
?>
<section class="page-heading"><div><span class="eyebrow">DATA INVENTARIS</span><h1><?= $editing ? 'Edit Produk' : 'Tambah Produk' ?></h1><p>Lengkapi informasi produk dan harga. Kolom bertanda wajib harus diisi.</p></div></section>
<form class="panel form-grid product-form" method="post" enctype="multipart/form-data" action="<?= $editing ? '/products/' . (int) $product['id'] : '/products' ?>">
    <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
    <label for="sku"><span class="field-title">SKU <span class="required-mark">Wajib</span></span><input id="sku" name="sku" required maxlength="50" placeholder="Contoh: SKU-001" value="<?= Http::e($product['sku'] ?? '') ?>"></label>
    <label for="name"><span class="field-title">Nama produk <span class="required-mark">Wajib</span></span><input id="name" name="name" required maxlength="150" placeholder="Masukkan nama produk" value="<?= Http::e($product['name'] ?? '') ?>"></label>
    <label for="category_id"><span class="field-title">Kategori <span class="required-mark">Wajib</span></span><select id="category_id" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= Http::e($category['name']) ?></option><?php endforeach; ?></select></label>
    <label for="unit"><span class="field-title">Satuan <span class="required-mark">Wajib</span></span><input id="unit" name="unit" required maxlength="20" placeholder="Contoh: pcs" value="<?= Http::e($product['unit'] ?? 'pcs') ?>"></label>
    <label for="purchase_price">Harga beli<input id="purchase_price" name="purchase_price" type="number" min="0" step="0.01" required value="<?= Http::e($product['purchase_price'] ?? '0') ?>"></label>
    <label for="selling_price">Harga jual<input id="selling_price" name="selling_price" type="number" min="0" step="0.01" required value="<?= Http::e($product['selling_price'] ?? '0') ?>"></label>
    <label for="reorder_point">Batas stok minimum<input id="reorder_point" name="reorder_point" type="number" min="0" step="1" required value="<?= Http::e($product['reorder_point'] ?? '0') ?>"></label>
    <label for="image">Foto produk <small>JPEG, PNG, atau WebP · Maksimal 2 MB</small><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
    <?php if (!empty($product['image_path'])): ?><img class="product-form-image" src="<?= Http::e($product['image_path']) ?>" alt="Gambar <?= Http::e($product['name']) ?>" width="160"><?php endif; ?>
    <div class="form-actions"><button type="submit"><?= $editing ? 'Simpan Perubahan' : 'Simpan Produk' ?></button><a class="button button-secondary" href="/products">Batal</a></div>
</form>
