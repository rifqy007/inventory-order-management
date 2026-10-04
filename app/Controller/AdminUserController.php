<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\Http;
use PDO;
use Throwable;

final class AdminUserController
{
    private const USERS_PATH = '/admin/users/';
    private const EDIT_PATH = '/edit';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function index(): void
    {
        Http::requireRole(['Admin']);
        $rows = $this->pdo->query(
            'SELECT id, name, email, role, is_active, created_at, updated_at
             FROM users
             ORDER BY name ASC'
        )->fetchAll();
        Http::view('admin/users-index', ['rows' => $rows]);
    }

    public function form(?int $id = null): void
    {
        Http::requireRole(['Admin']);
        $row = null;
        if ($id !== null) {
            $statement = $this->pdo->prepare(
                'SELECT id, name, email, role, is_active
                 FROM users
                 WHERE id = :id
                 LIMIT 1'
            );
            $statement->execute(['id' => $id]);
            $row = $statement->fetch() ?: null;
        }
        Http::view('admin/users-form', ['row' => $row]);
    }

    public function save(?int $id = null): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = (string) ($_POST['role'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (
            $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($role, ['Admin', 'Sales', 'WarehouseStaff'], true)
        ) {
            Http::flash('error', 'Data user tidak valid.');
            Http::redirect($id === null ? '/admin/users/create' : self::USERS_PATH . $id . self::EDIT_PATH);
        }
        try {
            if ($id === null) {
                $password = (string) ($_POST['password'] ?? '');
                if (strlen($password) < 8) {
                    Http::flash('error', 'Password minimal 8 karakter.');
                    Http::redirect('/admin/users/create');
                }
                $statement = $this->pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role, is_active)
                     VALUES (:name, :email, :password_hash, :role, :is_active)'
                );
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'is_active' => $active,
                ]);
            } else {
                $statement = $this->pdo->prepare(
                    'UPDATE users
                     SET name = :name, email = :email, role = :role, is_active = :is_active
                     WHERE id = :id'
                );
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'is_active' => $active,
                    'id' => $id,
                ]);
            }
            Http::flash('success', 'Data user berhasil disimpan.');
        } catch (Throwable) {
            Http::flash('error', 'Email sudah digunakan atau data gagal disimpan.');
        }
        Http::redirect('/admin/users');
    }

    public function changeStatus(int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $statement = $this->pdo->prepare(
            'UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        Http::flash('success', 'Status user berhasil diperbarui.');
        Http::redirect('/admin/users');
    }

    public function resetPassword(int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            Http::flash('error', 'Password minimal 8 karakter.');
            Http::redirect(self::USERS_PATH . $id . self::EDIT_PATH);
        }
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
        Http::flash('success', 'Password user berhasil direset.');
        Http::redirect(self::USERS_PATH . $id . self::EDIT_PATH);
    }
}
