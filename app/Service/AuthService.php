<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\InventoryRepositoryInterface;

final class AuthService
{
    public function __construct(private InventoryRepositoryInterface $users)
    {
    }
    /** Mengembalikan data aman untuk session. Pesan gagal disamakan agar tidak membocorkan email terdaftar. */
    public function authenticate(string $email, string $password): ?array
    {
        $u = $this->users->findUserByEmail($email);
        if (!$u || !(bool)$u['is_active'] || !password_verify($password, $u['password_hash'])) {
            return null;
        }
        return ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
    }
}
