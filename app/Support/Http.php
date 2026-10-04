<?php

declare(strict_types=1);

namespace App\Support;

final class Http
{
    /** Escape output mencegah HTML/JavaScript milik pengguna dijalankan sebagai XSS. */
    public static function e(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
    public static function requireLogin(): array
    {
        $u = self::user();
        if (!$u) {
            self::redirect('/login');
        }
        return $u;
    }
    public static function requireRole(array $roles): array
    {
        $u = self::requireLogin();
        if (!in_array($u['role'], $roles, true)) {
            http_response_code(403);
            self::view('errors/403');
            exit;
        }
        return $u;
    }
    public static function csrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
    public static function verifyCsrf(): void
    {
        if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_token'] ?? ''))) {
            http_response_code(403);
            self::view('errors/403');
            exit;
        }
    }
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = compact('type', 'message');
    }
    public static function view(string $name, array $data = []): void // NOSONAR: $name is consumed by the included layout template.
    {
        extract($data);
        $user = self::user(); // NOSONAR: layout.php renders the authenticated account details.
        $flash = $_SESSION['flash'] ?? null; // NOSONAR: layout.php renders and displays the flash message.
        unset($_SESSION['flash']);
        require dirname(__DIR__, 2) . '/views/layout.php';
    }
}
