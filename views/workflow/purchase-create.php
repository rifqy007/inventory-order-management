<?php

declare(strict_types=1);

use App\Support\Http;
?>
<section class="page-heading">
    <div><span class="eyebrow">PEMBELIAN</span><h1>Buat Purchase Order</h1><p>Buat draft pemesanan barang kepada supplier untuk gudang tujuan.</p></div>
    <a class="button button-secondary" href="<?= Http::e($returnTo) ?>">Kembali ke daftar</a>
</section>
<form class="panel order-form" method="post" action="/purchase-orders/workflow">
    <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
    <input type="hidden" name="return_to" value="<?= Http::e($returnTo) ?>">
    <section class="form-section">
        <div class="form-section-heading"><span class="step-number">1</span><div><h2>Informasi pesanan</h2><p>Tentukan supplier dan lokasi penerimaan barang.</p></div></div>
        <div class="form-grid">
            <label for="supplier_id">Supplier
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Pilih supplier</option>
                    <?php foreach ($suppliers as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" data-category-id="<?= (int) $row['category_id'] ?>"><?= Http::e($row['name'] . ' — ' . $row['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="warehouse_id">Gudang tujuan
                <select id="warehouse_id" name="warehouse_id" required><option value="">Pilih gudang</option><?php foreach ($warehouses as $row): ?><option value="<?= (int) $row['id'] ?>"><?= Http::e($row['name']) ?></option><?php endforeach; ?></select>
            </label>
        </div>
    </section>
    <section class="form-section">
        <div class="form-section-heading"><span class="step-number">2</span><div><h2>Produk yang dipesan</h2><p id="supplier-category-hint">Pilih supplier terlebih dahulu; produk akan mengikuti kategori supplier.</p></div></div>
        <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="order-item-row">
                <label for="product_<?= $i ?>">Produk
                    <select id="product_<?= $i ?>" name="product_id[]"><option value="">Pilih produk</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>" data-category-id="<?= (int) $product['category_id'] ?>" data-price="<?= Http::e($product['purchase_price']) ?>"><?= Http::e($product['sku'] . ' — ' . $product['name']) ?></option><?php endforeach; ?></select>
                </label>
                <label for="quantity_<?= $i ?>">Jumlah<input id="quantity_<?= $i ?>" type="number" min="1" name="quantity[]" value="1" disabled></label>
                <label for="price_<?= $i ?>">Harga beli satuan<input id="price_<?= $i ?>" class="catalog-price" type="text" value="Pilih produk" readonly aria-readonly="true"></label>
            </div>
        <?php endfor; ?>
    </section>
    <div class="form-actions"><button type="submit">Simpan sebagai Draf</button><a class="button button-secondary" href="/purchase-orders/workflow">Batal</a></div>
</form>
<script>
document.querySelectorAll('.order-item-row').forEach((row) => {
    const product = row.querySelector('select');
    const quantity = row.querySelector('input[name="quantity[]"]');
    const price = row.querySelector('.catalog-price');
    quantity.disabled = !product.value;
    product.addEventListener('change', () => {
        quantity.disabled = !product.value;
        quantity.required = Boolean(product.value);
        if (!product.value) quantity.value = '';
        else if (!quantity.value || Number(quantity.value) < 1) quantity.value = '1';
        const value = product.selectedOptions[0]?.dataset.price;
        price.value = value ? new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', maximumFractionDigits: 0}).format(Number(value)) : 'Pilih produk';
    });
});

const supplier = document.getElementById('supplier_id');
const categoryHint = document.getElementById('supplier-category-hint');
const productSelects = [...document.querySelectorAll('.order-item-row select[name="product_id[]"]')];
const updateProductsForSupplier = () => {
    const selectedSupplier = supplier.selectedOptions[0];
    const categoryId = selectedSupplier?.dataset.categoryId || '';
    const categoryName = selectedSupplier?.textContent.split(' — ').at(-1)?.trim() || '';
    categoryHint.textContent = categoryId
        ? `Produk dibatasi ke kategori ${categoryName}.`
        : 'Pilih supplier terlebih dahulu; produk akan mengikuti kategori supplier.';

    productSelects.forEach((select) => {
        [...select.options].forEach((option) => {
            if (!option.value) return;
            option.hidden = !categoryId || option.dataset.categoryId !== categoryId;
            option.disabled = option.hidden;
        });
        if (select.selectedOptions[0]?.disabled) {
            select.value = '';
            select.dispatchEvent(new Event('change'));
        }
    });
};
supplier.addEventListener('change', updateProductsForSupplier);
updateProductsForSupplier();

const purchaseForm = document.querySelector('form.order-form');
purchaseForm.addEventListener('submit', (event) => {
    const hasProduct = productSelects.some((select) => select.value !== '');
    if (hasProduct) return;
    event.preventDefault();
    const firstProduct = productSelects[0];
    firstProduct.setCustomValidity('Pilih minimal satu produk untuk Purchase Order.');
    firstProduct.reportValidity();
});
productSelects.forEach((select) => {
    select.addEventListener('change', () => select.setCustomValidity(''));
});
</script>
