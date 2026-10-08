<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\Http;
use App\Support\ListOptions;
use PDO;
use Throwable;

final class AdminUserController
{
    private const USERS_BASE_PATH = '/admin/users';
    private const USERS_PATH = self::USERS_BASE_PATH . '/';
    private const USER_CREATE_PATH = self::USERS_BASE_PATH . '/create';
    private const EDIT_PATH = '/edit';
    private const RETURN_TO_QUERY = '?return_to=';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function index(): void
    {
        Http::requireRole(['Admin']);
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'direction' => (string) ($_GET['direction'] ?? 'asc'),
            'sort' => (string) ($_GET['sort'] ?? 'name'),
        ];
        $options = ListOptions::fromRequest($filters, ['name', 'email', 'role', 'is_active'], 'name');
        $filters['page'] = $options->page;
        $filters['per_page'] = $options->perPage;
        $filters['direction'] = $options->direction;
        $filters['sort'] = $options->sort;
        $sortColumns = ['name' => 'name', 'email' => 'email', 'role' => 'role', 'is_active' => 'is_active'];
        $where = '';
        $parameters = [];
        if ($filters['q'] !== '') {
            $where = ' WHERE name LIKE :name_search OR email LIKE :email_search OR role LIKE :role_search';
            $search = '%' . $filters['q'] . '%';
            $parameters = [
                'name_search' => $search,
                'email_search' => $search,
                'role_search' => $search,
            ];
        }
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM users' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $filters['per_page']));
        $filters['page'] = min($filters['page'], $pages);
        $offset = ($filters['page'] - 1) * $filters['per_page'];
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, role, is_active FROM users' . $where
            . ' ORDER BY ' . $sortColumns[$filters['sort']] . ' ' . $options->sqlDirection()
            . ', id ASC LIMIT ' . $filters['per_page'] . ' OFFSET ' . $offset
        );
        $statement->execute($parameters);
        Http::view('admin/users-index', [
            'rows' => $statement->fetchAll(),
            'filters' => $filters,
            'total' => $total,
            'pages' => $pages,
        ]);
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
        Http::view('admin/users-form', [
            'row' => $row,
            'returnTo' => Http::returnTo(self::USERS_BASE_PATH),
        ]);
    }

    public function save(?int $id = null): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo(self::USERS_BASE_PATH);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = (string) ($_POST['role'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (
            $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($role, ['Admin', 'Sales', 'WarehouseStaff'], true)
        ) {
            Http::flash('error', 'Data user tidak valid.');
            Http::redirect(($id === null ? self::USER_CREATE_PATH : self::USERS_PATH . $id . self::EDIT_PATH) . self::RETURN_TO_QUERY . rawurlencode($returnTo));
        }
        try {
            if ($id === null) {
                $password = (string) ($_POST['password'] ?? '');
                if (strlen($password) < 8) {
                    Http::flash('error', 'Password minimal 8 karakter.');
                    Http::redirect(self::USER_CREATE_PATH . self::RETURN_TO_QUERY . rawurlencode($returnTo));
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
        Http::redirect($returnTo);
    }

    public function changeStatus(int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo(self::USERS_BASE_PATH);
        $statement = $this->pdo->prepare(
            'UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        Http::flash('success', 'Status user berhasil diperbarui.');
        Http::redirect($returnTo);
    }

    public function resetPassword(int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo(self::USERS_BASE_PATH);
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            Http::flash('error', 'Password minimal 8 karakter.');
            Http::redirect(self::USERS_PATH . $id . self::EDIT_PATH . self::RETURN_TO_QUERY . rawurlencode($returnTo));
        }
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
        Http::flash('success', 'Password user berhasil direset.');
        Http::redirect(self::USERS_PATH . $id . self::EDIT_PATH . self::RETURN_TO_QUERY . rawurlencode($returnTo));
    }
}
