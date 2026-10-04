<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Support\Http;

final class AuthController
{
    public function __construct(private AuthService $service)
    {
    }
    public function form(): void
    {
        if (Http::user()) {
            Http::redirect('/dashboard');
        }
        Http::view('auth/login');
    }
    public function login(): void
    {
        Http::verifyCsrf();
        $u = $this->service->authenticate(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''));
        if (!$u) {
            Http::flash('error', 'Email atau password tidak valid.');
            Http::redirect('/login');
        }
        session_regenerate_id(true);
        $_SESSION['user'] = $u;
        Http::redirect('/dashboard');
    }
    public function logout(): void
    {
        Http::verifyCsrf();
        $_SESSION = [];
        session_destroy();
        Http::redirect('/login');
    }
}
