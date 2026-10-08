<?php
declare(strict_types=1);
use App\Support\Http;
$editing = $product !== null;
$formatRupiahInput = static function (mixed $amount): string {
    $formatted = number_format((float) $amount, 2, ',', '.');
    return rtrim(rtrim($formatted, '0'), ',');
};
?>
<section class="page-heading"><div><span class="eyebrow">DATA INVENTARIS</span><h1><?= $editing ? 'Edit Produk' : 'Tambah Produk' ?></h1><p>Lengkapi informasi produk dan harga. Kolom bertanda wajib harus diisi.</p></div></section>
<form class="panel form-grid product-form" method="post" enctype="multipart/form-data" action="<?= $editing ? '/products/' . (int) $product['id'] : '/products' ?>">
    <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
    <input type="hidden" name="return_to" value="<?= Http::e($returnTo) ?>">
    <label for="sku"><span class="field-title">SKU <span class="required-mark">Wajib</span></span><input id="sku" name="sku" required maxlength="50" placeholder="Contoh: SKU-001" value="<?= Http::e($product['sku'] ?? '') ?>"></label>
    <label for="name"><span class="field-title">Nama produk <span class="required-mark">Wajib</span></span><input id="name" name="name" required maxlength="150" placeholder="Masukkan nama produk" value="<?= Http::e($product['name'] ?? '') ?>"></label>
    <label for="category_id"><span class="field-title">Kategori <span class="required-mark">Wajib</span></span><select id="category_id" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= Http::e($category['name']) ?></option><?php endforeach; ?></select></label>
    <label for="unit"><span class="field-title">Satuan <span class="required-mark">Wajib</span></span><input id="unit" name="unit" required maxlength="20" placeholder="Contoh: pcs" value="<?= Http::e($product['unit'] ?? 'pcs') ?>"></label>
    <label for="purchase_price">Harga beli <small>Masukkan nominal Rupiah</small><span class="currency-input"><span aria-hidden="true">Rp</span><input id="purchase_price" name="purchase_price" type="text" inputmode="decimal" autocomplete="off" placeholder="10.000" data-currency-input required value="<?= Http::e($formatRupiahInput($product['purchase_price'] ?? '0')) ?>"></span></label>
    <label for="selling_price">Harga jual <small>Masukkan nominal Rupiah</small><span class="currency-input"><span aria-hidden="true">Rp</span><input id="selling_price" name="selling_price" type="text" inputmode="decimal" autocomplete="off" placeholder="25.000" data-currency-input required value="<?= Http::e($formatRupiahInput($product['selling_price'] ?? '0')) ?>"></span></label>
    <label for="reorder_point">Batas stok minimum<input id="reorder_point" name="reorder_point" type="number" min="0" step="1" required value="<?= Http::e($product['reorder_point'] ?? '0') ?>"></label>
    <div class="product-image-field">
        <label for="image">Foto produk <small>JPEG, PNG, atau WebP · Maksimal 2 MB</small><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
        <?php if (!empty($product['image_path'])): ?><figure class="product-form-preview"><img class="product-form-image" src="<?= Http::e($product['image_path']) ?>" alt="Gambar <?= Http::e($product['name']) ?>"><figcaption>Foto yang tersimpan saat ini</figcaption></figure><?php endif; ?>
    </div>
    <div class="form-actions"><button type="submit"><?= $editing ? 'Simpan Perubahan' : 'Simpan Produk' ?></button><a class="button button-secondary" href="<?= Http::e($returnTo) ?>">Batal</a></div>
</form>
