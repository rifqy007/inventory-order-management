<?php

declare(strict_types=1);

use App\Support\Http;
use App\Support\TableView;

$pages = max(1, (int) $pages);
$usersBasePath = '/admin/users';
$query = $filters;
$returnTo = $usersBasePath . '?' . http_build_query($filters);
?>
<header class="page-heading">
    <div>
        <h1>Manajemen User</h1>
        <p>Kelola akun internal, role, dan status akses aplikasi.</p>
    </div>
    <a class="button" href="<?= Http::e($usersBasePath . '/create') ?>?return_to=<?= rawurlencode($returnTo) ?>">Tambah User</a>
</header>
<form class="master-search" method="get" action="<?= Http::e($usersBasePath) ?>">
    <label for="user-search-input">Cari pengguna</label>
    <input id="user-search-input" type="search" name="q" value="<?= Http::e($filters['q']) ?>" placeholder="Cari nama, email, atau role">
    <input type="hidden" name="sort" value="<?= Http::e($filters['sort']) ?>">
    <input type="hidden" name="direction" value="<?= Http::e($filters['direction']) ?>">
    <input type="hidden" name="per_page" value="<?= (int) $filters['per_page'] ?>">
    <input type="hidden" name="page" value="1">
    <button type="submit">Cari</button>
    <?php if ($filters['q'] !== ''): ?><a class="button button-secondary" href="<?= Http::e($usersBasePath) ?>">Reset</a><?php endif; ?>
</form>
<p class="master-result-count"><?= (int) $total ?> pengguna ditemukan.</p>
<div class="card table responsive-table">
    <table>
        <thead>
            <tr>
                <?= TableView::sortHeader($filters, 'name', 'Nama', $usersBasePath) ?>
                <?= TableView::sortHeader($filters, 'email', 'Email', $usersBasePath) ?>
                <?= TableView::sortHeader($filters, 'role', 'Role', $usersBasePath) ?>
                <?= TableView::sortHeader($filters, 'is_active', 'Status', $usersBasePath) ?>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="empty-state">Tidak ada pengguna yang cocok dengan pencarian.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td data-label="Nama"><?= Http::e($row['name']) ?></td>
                        <td data-label="Email"><?= Http::e($row['email']) ?></td>
                        <td data-label="Role"><?= Http::e($row['role']) ?></td>
                        <td data-label="Status"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
                        <td data-label="Aksi">
                            <div class="actions">
                                <a class="button secondary" href="<?= Http::e($usersBasePath . '/' . (int) $row['id'] . '/edit') ?>?return_to=<?= rawurlencode($returnTo) ?>">Edit</a>
                                <form method="post" action="<?= Http::e($usersBasePath . '/' . (int) $row['id'] . '/status') ?>" data-confirm="Ubah status user ini?">
                                    <input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>">
                                    <input type="hidden" name="return_to" value="<?= Http::e($returnTo) ?>">
                                    <button class="danger" type="submit"><?= $row['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="table-controls">
    <form class="table-footer" method="get" action="<?= Http::e($usersBasePath) ?>">
        <label for="user-rows-per-page">Baris per halaman
            <select id="user-rows-per-page" name="per_page">
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
    <nav class="pagination" aria-label="Navigasi halaman pengguna">
        <?php if ($filters['page'] > 1): $query['page'] = $filters['page'] - 1; ?>
            <a href="<?= Http::e($usersBasePath) ?>?<?= Http::e(http_build_query($query)) ?>">Sebelumnya</a>
        <?php endif; ?>
        <span>Halaman <?= (int) $filters['page'] ?> dari <?= $pages ?></span>
        <?php if ($filters['page'] < $pages): $query['page'] = $filters['page'] + 1; ?>
            <a href="<?= Http::e($usersBasePath) ?>?<?= Http::e(http_build_query($query)) ?>">Berikutnya</a>
        <?php endif; ?>
    </nav>
</div>
