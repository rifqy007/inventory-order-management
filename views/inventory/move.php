<?php

declare(strict_types=1);

use App\Support\Http;

/**
 * Halaman Pergerakan Stok
 *
 * Fungsi:
 * Menampilkan form untuk mencatat penerimaan, pengeluaran, atau
 * penyesuaian stok pada produk dan gudang yang dipilih.
 *
 * Catatan alur Project Brief:
 * Perubahan stok resmi dari PO dan SO nantinya tetap harus dilakukan
 * melalui Goods Receipt dan Goods Issue. Form ini dapat digunakan untuk
 * pencatatan manual atau penyesuaian oleh role yang berwenang.
 */
?>

<section class="page-heading">
    <h1>Pergerakan Stok</h1>
    <p>Pilih produk, gudang, jenis pergerakan, dan jumlah barang.</p>
</section>

<section class="panel stock-panel">
    <h2>Catat Pergerakan</h2>
    <p class="stock-description">
        Pastikan produk, gudang, dan jumlah barang sudah sesuai sebelum diproses.
    </p>

    <form method="post" action="/inventory/move" class="stock-form">
        <input
            type="hidden"
            name="_token"
            value="<?= Http::e(Http::csrf()) ?>">

        <div class="form-group">
            <label for="product_id">Produk</label>
            <select id="product_id" name="product_id" required>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>">
                        <?= Http::e($product['sku'] . ' - ' . $product['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="warehouse_id">Gudang</label>
            <select id="warehouse_id" name="warehouse_id" required>
                <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?= (int) $warehouse['id'] ?>">
                        <?= Http::e($warehouse['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="type">Jenis Pergerakan</label>
            <select id="type" name="type" required>
                <option value="Receipt">Penerimaan</option>
                <option value="Issue">Pengeluaran</option>
                <option value="Adjustment">Penyesuaian</option>
            </select>
        </div>

        <div class="form-group">
            <label for="quantity">Jumlah</label>
            <input
                id="quantity"
                type="number"
                name="quantity"
                min="1"
                step="1"
                inputmode="numeric"
                required>
        </div>

        <div class="stock-form-actions">
            <button type="submit">Proses Pergerakan</button>
        </div>
    </form>
</section>
