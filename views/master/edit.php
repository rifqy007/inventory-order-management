<?php

declare(strict_types=1);

use App\Support\Http;

$labels = [
    'categories' => 'Kategori',
    'warehouses' => 'Gudang',
    'suppliers' => 'Supplier',
    'customers' => 'Customer',
];
$title = $labels[$table] ?? 'Master Data';
?>

<section class="page-heading"><div><span class="eyebrow">DATA MASTER</span><h1>Edit <?= Http::e($title) ?></h1><p>Perbarui informasi, lalu simpan perubahan.</p></div></section>

<section class="panel">
    <form method="post" action="/<?= Http::e($table) ?>/<?= (int) $row['id'] ?>" class="form-grid">
        <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">

        <label>
            Nama
            <input type="text" name="name" value="<?= Http::e($row['name'] ?? '') ?>" required>
        </label>

        <?php if ($table === 'categories'): ?>
            <label class="wide-field">
                Deskripsi
                <textarea name="description" rows="3"><?= Http::e($row['description'] ?? '') ?></textarea>
            </label>
        <?php elseif ($table === 'warehouses'): ?>
            <label class="wide-field">
                Lokasi
                <textarea name="location" rows="3"><?= Http::e($row['location'] ?? '') ?></textarea>
            </label>
        <?php else: ?>
            <label>
                Kontak
                <input type="text" name="contact" value="<?= Http::e($row['contact'] ?? '') ?>">
            </label>
            <label class="wide-field">
                Alamat
                <textarea name="address" rows="3"><?= Http::e($row['address'] ?? '') ?></textarea>
            </label>
        <?php endif; ?>

        <div class="action-group">
            <button type="submit">Simpan Perubahan</button>
            <a class="button button-secondary" href="/<?= Http::e($table) ?>">Batal</a>
        </div>
    </form>
</section>
