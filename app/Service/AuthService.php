<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InactiveUserException;
use App\Repository\InventoryRepositoryInterface;

final class AuthService
{
    public function __construct(private InventoryRepositoryInterface $users)
    {
    }
    /** Null untuk kredensial salah; akun nonaktif hanya dikenali setelah password terbukti benar. */
    public function authenticate(string $email, string $password): ?array
    {
        $u = $this->users->findUserByEmail($email);
        if (!$u || !password_verify($password, $u['password_hash'])) {
            return null;
        }
        if (!(bool) $u['is_active']) {
            throw new InactiveUserException('Akun tidak aktif. Silakan hubungi admin.');
        }
        return ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
    }
}
