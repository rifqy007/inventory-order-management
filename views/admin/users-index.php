<?php

declare(strict_types=1);

use App\Support\Http; ?>
<header class="page-heading">
    <div>
        <h1>Manajemen User</h1>
        <p>Kelola akun internal, role, dan status akses aplikasi.</p>
    </div><a class="button" href="/admin/users/create">Tambah User</a>
</header>
<div class="card table">
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody><?php foreach ($rows as $row): ?><tr>
                    <td><?= Http::e($row['name']) ?></td>
                    <td><?= Http::e($row['email']) ?></td>
                    <td><?= Http::e($row['role']) ?></td>
                    <td><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
                    <td>
                        <div class="actions"><a class="button secondary" href="/admin/users/<?= (int) $row['id'] ?>/edit">Edit</a>
                            <form method="post" action="/admin/users/<?= (int) $row['id'] ?>/status" data-confirm="Ubah status user ini?"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><button class="danger" type="submit"><?= $row['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                        </div>
                    </td>
                </tr><?php endforeach; ?></tbody>
    </table>
</div>
