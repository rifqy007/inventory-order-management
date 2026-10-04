<?php

declare(strict_types=1);

use App\Support\Http;
?>
<section class="page-heading">
    <div><span class="eyebrow">PEMBELIAN</span><h1>Buat Purchase Order</h1><p>Buat draft pemesanan barang kepada supplier untuk gudang tujuan.</p></div>
    <a class="button button-secondary" href="/purchase-orders/workflow">Kembali ke daftar</a>
</section>
<form class="panel order-form" method="post" action="/purchase-orders/workflow">
    <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
    <section class="form-section">
        <div class="form-section-heading"><span class="step-number">1</span><div><h2>Informasi pesanan</h2><p>Tentukan supplier dan lokasi penerimaan barang.</p></div></div>
        <div class="form-grid">
            <label for="supplier_id">Supplier<select id="supplier_id" name="supplier_id" required><?php foreach ($suppliers as $row): ?><option value="<?= (int) $row['id'] ?>"><?= Http::e($row['name']) ?></option><?php endforeach; ?></select></label>
            <label for="warehouse_id">Gudang tujuan<select id="warehouse_id" name="warehouse_id" required><?php foreach ($warehouses as $row): ?><option value="<?= (int) $row['id'] ?>"><?= Http::e($row['name']) ?></option><?php endforeach; ?></select></label>
        </div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><span class="step-number">2</span><div><h2>Produk yang dipesan</h2><p>Isi minimal satu produk. Harga mengikuti katalog produk.</p></div></div>
        <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="order-item-row">
                <label for="product_<?= $i ?>">Produk<select id="product_<?= $i ?>" name="product_id[]"><option value="">Pilih produk</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>" data-price="<?= Http::e($product['purchase_price']) ?>"><?= Http::e($product['sku'] . ' — ' . $product['name']) ?></option><?php endforeach; ?></select></label>
                <label for="quantity_<?= $i ?>">Jumlah<input id="quantity_<?= $i ?>" type="number" min="1" name="quantity[]" value="1"></label>
                <label for="price_<?= $i ?>">Harga beli satuan<input id="price_<?= $i ?>" class="catalog-price" type="text" value="Pilih produk" readonly aria-readonly="true"></label>
            </div>
        <?php endfor; ?>
    </section>
    <div class="form-actions"><button type="submit">Simpan sebagai Draft</button><a class="button button-secondary" href="/purchase-orders/workflow">Batal</a></div>
</form>
<script>
document.querySelectorAll('.order-item-row').forEach((row) => {
    const product = row.querySelector('select');
    const price = row.querySelector('.catalog-price');
    product.addEventListener('change', () => {
        const value = product.selectedOptions[0]?.dataset.price;
        price.value = value ? new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', maximumFractionDigits: 0}).format(Number(value)) : 'Pilih produk';
    });
});
</script>
