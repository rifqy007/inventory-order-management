<?php

declare(strict_types=1);

use App\Support\Http;

$editing = is_array($row); ?>
<header class="page-heading">
    <div>
        <h1><?= $editing ? 'Edit User' : 'Tambah User' ?></h1>
        <p>Akun internal untuk Admin, Sales, dan Warehouse Staff.</p>
    </div>
</header>
<div class="card">
    <form class="form-grid" method="post" action="<?= $editing ? '/admin/users/' . (int) $row['id'] : '/admin/users' ?>"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><label>Nama<input name="name" required value="<?= Http::e($row['name'] ?? '') ?>"></label><label>Email<input type="email" name="email" required value="<?= Http::e($row['email'] ?? '') ?>"></label><label>Role<select name="role"><?php foreach (['Admin', 'Sales', 'WarehouseStaff'] as $role): ?><option value="<?= $role ?>" <?= ($row['role'] ?? '') === $role ? 'selected' : '' ?>><?= $role ?></option><?php endforeach; ?></select></label><?php if (!$editing): ?><label>Password Awal<input type="password" name="password" minlength="8" required></label><?php endif; ?><label class="checkbox-field">Status<input type="checkbox" name="is_active" <?= ($row['is_active'] ?? 1) ? 'checked' : '' ?>> <span>Akun aktif</span></label>
        <div class="actions"><button type="submit">Simpan User</button><a class="button secondary" href="/admin/users">Batal</a></div>
    </form><?php if ($editing): ?><form class="reset-form" method="post" action="/admin/users/<?= (int) $row['id'] ?>/reset-password"><input type="hidden" name="_token" value="<?= Http::e(Http::csrf()) ?>"><label>Password Baru<input type="password" name="password" minlength="8" required></label><button class="warning" type="submit">Reset Password</button></form><?php endif; ?>
</div>
