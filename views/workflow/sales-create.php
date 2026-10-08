<?php

declare(strict_types=1);

use App\Support\Http;
?>
<section class="page-heading">
    <div><span class="eyebrow">PENJUALAN</span><h1>Buat Sales Order</h1><p>Pilih customer, gudang, dan produk untuk membuat draft pesanan.</p></div>
    <a class="button button-secondary" href="<?= Http::e($returnTo) ?>">Kembali ke daftar</a>
</section>
<form class="panel order-form" method="post" action="/sales-orders/workflow">
    <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
    <input type="hidden" name="return_to" value="<?= Http::e($returnTo) ?>">
    <section class="form-section">
        <div class="form-section-heading"><span class="step-number">1</span><div><h2>Informasi pesanan</h2><p>Tentukan customer dan lokasi pengambilan barang.</p></div></div>
        <div class="form-grid">
            <label for="customer_id">Customer<select id="customer_id" name="customer_id" required><?php foreach ($customers as $row): ?><option value="<?= (int) $row['id'] ?>"><?= Http::e($row['name']) ?></option><?php endforeach; ?></select></label>
            <label for="warehouse_id">Gudang asal<select id="warehouse_id" name="warehouse_id" required><?php foreach ($warehouses as $row): ?><option value="<?= (int) $row['id'] ?>"><?= Http::e($row['name']) ?></option><?php endforeach; ?></select></label>
        </div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><span class="step-number">2</span><div><h2>Produk yang dipesan</h2><p>Isi minimal satu produk. Harga mengikuti katalog produk.</p></div></div>
        <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="order-item-row">
                <label for="product_<?= $i ?>">Produk<select id="product_<?= $i ?>" name="product_id[]"><option value="">Pilih produk</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>" data-sku="<?= Http::e($product['sku']) ?>" data-price="<?= Http::e($product['selling_price']) ?>"><?= Http::e($product['sku'] . ' — ' . $product['name']) ?></option><?php endforeach; ?></select></label>
                <label for="quantity_<?= $i ?>">Jumlah<input id="quantity_<?= $i ?>" type="number" min="1" name="quantity[]" value="1"></label>
                <label for="price_<?= $i ?>">Harga satuan<input id="price_<?= $i ?>" class="catalog-price" type="text" value="Pilih produk" readonly aria-readonly="true"></label>
                <output class="availability" aria-live="polite"></output>
            </div>
        <?php endfor; ?>
    </section>
    <div class="form-actions"><button type="submit">Simpan sebagai Draft</button><a class="button button-secondary" href="/sales-orders/workflow">Batal</a></div>
</form>
<script>
document.querySelectorAll('.order-item-row').forEach((row) => {
    const product = row.querySelector('select');
    const price = row.querySelector('.catalog-price');
    product.addEventListener('change', async () => {
        const option = product.selectedOptions[0];
        const output = row.querySelector('.availability');
        price.value = option?.dataset.price ? new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', maximumFractionDigits: 0}).format(Number(option.dataset.price)) : 'Pilih produk';
        output.textContent = '';
        if (!option?.dataset.sku) return;
        try {
            const response = await fetch(`/api/products/${encodeURIComponent(option.dataset.sku)}/availability`, {headers: {'Accept': 'application/json'}});
            const stocks = await response.json();
            output.textContent = response.ok ? stocks.map((stock) => `${stock.warehouse}: ${stock.quantity}`).join(' · ') : stocks.error;
        } catch (_) { output.textContent = 'Stok tidak dapat dimuat.'; }
    });
});
</script>
