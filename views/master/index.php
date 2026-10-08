<?php

declare(strict_types=1);

use App\Support\Http;
use App\Support\TableView;

$labels = [
    'categories' => 'Kategori',
    'warehouses' => 'Gudang',
    'suppliers' => 'Supplier',
    'customers' => 'Customer',
    'users' => 'Pengguna',
];
$title = $labels[$table] ?? 'Master Data';
$isAdmin = ($user['role'] ?? '') === 'Admin';
$masterPath = '/' . $table;
$query = $filters;
$returnTo = $masterPath . '?' . http_build_query($filters);
?>

<section class="page-heading">
    <h1><?= Http::e($title) ?></h1>
</section>

<?php if ($isAdmin && $table !== 'users'): ?>
    <section class="panel master-form-panel">
        <div class="master-form-heading"><span class="step-number">＋</span><div><h2>Tambah <?= Http::e($title) ?></h2><p>Isi informasi <?= Http::e(strtolower($title)) ?> baru, lalu simpan.</p></div></div>

        <form method="post" action="/<?= Http::e($table) ?>" class="form-grid">
            <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
            <input type="hidden" name="return_to" value="<?= Http::e($returnTo) ?>">

            <label>
                <span class="field-title">Nama <span class="required-mark">Wajib</span></span>
            <input type="text" name="name" placeholder="Masukkan nama <?= Http::e(strtolower($title)) ?>" required>
            </label>

            <?php if ($table === 'suppliers'): ?>
                <label>Kategori produk
                    <select name="category_id" required>
                        <option value="">Pilih kategori produk</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>"><?= Http::e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>

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

<form class="master-search" method="get" action="<?= Http::e($masterPath) ?>">
    <label for="master-search-input">Cari <?= Http::e(strtolower($title)) ?></label>
    <input id="master-search-input" type="search" name="q" value="<?= Http::e($filters['q']) ?>" placeholder="Cari nama, kategori, kontak, alamat, atau keterangan">
    <input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>">
    <input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>">
    <input type="hidden" name="per_page" value="<?= (int) $filters['per_page'] ?>">
    <input type="hidden" name="page" value="1">
    <button type="submit">Cari</button>
    <?php if ($filters['q'] !== ''): ?><a class="button button-secondary" href="<?= Http::e($masterPath) ?>">Reset</a><?php endif; ?>
</form>
<p class="master-result-count"><?= (int) $total ?> data ditemukan.</p>

<div class="table responsive-table">
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <?= TableView::sortHeader($filters, 'name', 'Nama', $masterPath) ?>
                <?php if ($table === 'categories'): ?>
                    <?= TableView::sortHeader($filters, 'description', 'Deskripsi', $masterPath) ?>
                <?php elseif ($table === 'warehouses'): ?>
                    <?= TableView::sortHeader($filters, 'location', 'Lokasi', $masterPath) ?>
                <?php elseif (in_array($table, ['suppliers', 'customers'], true)): ?>
                    <?php if ($table === 'suppliers'): ?><?= TableView::sortHeader($filters, 'category', 'Kategori Produk', $masterPath) ?><?php endif; ?>
                    <?= TableView::sortHeader($filters, 'contact', 'Kontak', $masterPath) ?>
                    <?= TableView::sortHeader($filters, 'address', 'Alamat', $masterPath) ?>
                <?php elseif ($table === 'users'): ?>
                    <th>Email</th>
                    <th>Peran</th>
                <?php endif; ?>
                <?= TableView::sortHeader($filters, 'is_active', 'Status', $masterPath) ?>
                <?php if ($isAdmin && $table !== 'users'): ?>
                    <th>Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="7" class="empty-state">Tidak ada data yang cocok dengan pencarian.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php $active = (int) ($row['is_active'] ?? 1) === 1; ?>
                    <tr>
                        <td data-label="No."><?= (($filters['page'] - 1) * $filters['per_page']) + $index + 1 ?></td>
                        <td data-label="Nama"><?= Http::e($row['name'] ?? '') ?></td>

                        <?php if ($table === 'categories'): ?>
                            <td data-label="Deskripsi"><?= Http::e($row['description'] ?? '-') ?></td>
                        <?php elseif ($table === 'warehouses'): ?>
                            <td data-label="Lokasi"><?= Http::e($row['location'] ?? '-') ?></td>
                        <?php elseif (in_array($table, ['suppliers', 'customers'], true)): ?>
                            <?php if ($table === 'suppliers'): ?><td data-label="Kategori Produk"><?= Http::e($row['category'] ?? '-') ?></td><?php endif; ?>
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
                                    <a class="button button-secondary" href="/<?= Http::e($table) ?>/<?= (int) $row['id'] ?>/edit?return_to=<?= rawurlencode($returnTo) ?>">
                                        Edit
                                    </a>

                                    <form method="post" action="/<?= Http::e($table) ?>/<?= (int) $row['id'] ?>/status">
                                        <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
                                        <input type="hidden" name="active" value="<?= $active ? '0' : '1' ?>">
                                        <input type="hidden" name="return_to" value="<?= Http::e($returnTo) ?>">
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

<div class="table-controls">
    <form class="table-footer" method="get" action="<?= Http::e($masterPath) ?>">
        <label for="master-rows-per-page">Baris per halaman
            <select id="master-rows-per-page" name="per_page">
                <?php foreach ([10, 25, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= $filters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <input type="hidden" name="q" value="<?= Http::e($filters['q']) ?>">
        <input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>">
        <input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>">
        <input type="hidden" name="page" value="1">
        <button class="secondary" type="submit">Terapkan</button>
    </form>
    <nav class="pagination" aria-label="Navigasi halaman <?= Http::e($title) ?>">
        <?php if ($filters['page'] > 1): $query['page'] = $filters['page'] - 1; ?>
            <a href="<?= Http::e($masterPath) ?>?<?= Http::e(http_build_query($query)) ?>">Sebelumnya</a>
        <?php endif; ?>
        <span>Halaman <?= (int) $filters['page'] ?> dari <?= (int) $pages ?></span>
        <?php if ($filters['page'] < $pages): $query['page'] = $filters['page'] + 1; ?>
            <a href="<?= Http::e($masterPath) ?>?<?= Http::e(http_build_query($query)) ?>">Berikutnya</a>
        <?php endif; ?>
    </nav>
</div>
