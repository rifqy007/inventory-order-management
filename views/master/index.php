<?php

declare(strict_types=1);

use App\Support\Http;

$labels = [
    'categories' => 'Kategori',
    'warehouses' => 'Gudang',
    'suppliers' => 'Supplier',
    'customers' => 'Customer',
    'users' => 'Pengguna',
];
$title = $labels[$table] ?? 'Master Data';
$isAdmin = ($user['role'] ?? '') === 'Admin';
?>

<section class="page-heading">
    <h1><?= Http::e($title) ?></h1>
</section>

<?php if ($isAdmin && $table !== 'users'): ?>
    <section class="panel master-form-panel">
        <div class="master-form-heading"><span class="step-number">＋</span><div><h2>Tambah <?= Http::e($title) ?></h2><p>Isi informasi <?= Http::e(strtolower($title)) ?> baru, lalu simpan.</p></div></div>

        <form method="post" action="/<?= Http::e($table) ?>" class="form-grid">
            <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">

            <label>
                <span class="field-title">Nama <span class="required-mark">Wajib</span></span>
                <input type="text" name="name" placeholder="Masukkan nama <?= Http::e(strtolower($title)) ?>" required>
            </label>

            <?php if ($table === 'categories'): ?>
                <label class="wide-field">
                    Deskripsi
                    <textarea name="description" rows="3" placeholder="Tambahkan keterangan bila diperlukan"></textarea>
                </label>
            <?php elseif ($table === 'warehouses'): ?>
                <label class="wide-field">
                    Lokasi
                    <textarea name="location" rows="3" placeholder="Masukkan alamat atau lokasi gudang"></textarea>
                </label>
            <?php else: ?>
                <label>
                    Kontak
                    <input type="text" name="contact" placeholder="Nomor telepon atau email">
                </label>
                <label class="wide-field">
                    Alamat
                    <textarea name="address" rows="3" placeholder="Masukkan alamat lengkap"></textarea>
                </label>
            <?php endif; ?>

            <div class="master-form-actions"><button type="submit">Simpan <?= Http::e($title) ?></button></div>
        </form>
    </section>
<?php endif; ?>

<div class="table responsive-table">
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama</th>
                <?php if ($table === 'categories'): ?>
                    <th>Deskripsi</th>
                <?php elseif ($table === 'warehouses'): ?>
                    <th>Lokasi</th>
                <?php elseif (in_array($table, ['suppliers', 'customers'], true)): ?>
                    <th>Kontak</th>
                    <th>Alamat</th>
                <?php elseif ($table === 'users'): ?>
                    <th>Email</th>
                    <th>Peran</th>
                <?php endif; ?>
                <th>Status</th>
                <?php if ($isAdmin && $table !== 'users'): ?>
                    <th>Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="7" class="empty-state">Belum ada data.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php $active = (int) ($row['is_active'] ?? 1) === 1; ?>
                    <tr>
                        <td data-label="No."><?= $index + 1 ?></td>
                        <td data-label="Nama"><?= Http::e($row['name'] ?? '') ?></td>

                        <?php if ($table === 'categories'): ?>
                            <td data-label="Deskripsi"><?= Http::e($row['description'] ?? '-') ?></td>
                        <?php elseif ($table === 'warehouses'): ?>
                            <td data-label="Lokasi"><?= Http::e($row['location'] ?? '-') ?></td>
                        <?php elseif (in_array($table, ['suppliers', 'customers'], true)): ?>
                            <td data-label="Kontak"><?= Http::e($row['contact'] ?? '-') ?></td>
                            <td data-label="Alamat"><?= Http::e($row['address'] ?? '-') ?></td>
                        <?php elseif ($table === 'users'): ?>
                            <td data-label="Email"><?= Http::e($row['email'] ?? '-') ?></td>
                            <td data-label="Peran"><?= Http::e($row['role'] ?? '-') ?></td>
                        <?php endif; ?>

                        <td data-label="Status">
                            <span class="status-badge <?= $active ? 'status-active' : 'status-inactive' ?>">
                                <?= $active ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>

                        <?php if ($isAdmin && $table !== 'users'): ?>
                            <td data-label="Aksi">
                                <div class="action-group">
                                    <a class="button button-secondary" href="/<?= Http::e($table) ?>/<?= (int) $row['id'] ?>/edit">
                                        Edit
                                    </a>

                                    <form method="post" action="/<?= Http::e($table) ?>/<?= (int) $row['id'] ?>/status">
                                        <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
                                        <input type="hidden" name="active" value="<?= $active ? '0' : '1' ?>">
                                        <button type="submit" class="<?= $active ? 'button-danger' : 'button-success' ?>">
                                            <?= $active ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
