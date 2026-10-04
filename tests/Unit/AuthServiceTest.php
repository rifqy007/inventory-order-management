<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repository\FakeInventoryRepository;
use App\Service\AuthService;

final class AuthServiceTest extends TestCase
{
    public function test_valid_user_can_login(): void
    {
        $r = new FakeInventoryRepository();
        $r->users[] = ['id' => 1, 'name' => 'A', 'email' => 'a@b.test', 'password_hash' => password_hash('secret', PASSWORD_DEFAULT), 'role' => 'Admin', 'is_active' => 1];
        $this->assertSame('Admin', (new AuthService($r))->authenticate('a@b.test', 'secret')['role']);
    }
    public function test_wrong_password_is_rejected(): void
    {
        $r = new FakeInventoryRepository();
        $r->users[] = ['id' => 1, 'name' => 'A', 'email' => 'a@b.test', 'password_hash' => password_hash('secret', PASSWORD_DEFAULT), 'role' => 'Admin', 'is_active' => 1];
        $this->assertNull((new AuthService($r))->authenticate('a@b.test', 'wrong'));
    }
    public function test_inactive_user_is_rejected(): void
    {
        $r = new FakeInventoryRepository();
        $r->users[] = ['id' => 1, 'name' => 'A', 'email' => 'a@b.test', 'password_hash' => password_hash('secret', PASSWORD_DEFAULT), 'role' => 'Admin', 'is_active' => 0];
        $this->assertNull((new AuthService($r))->authenticate('a@b.test', 'secret'));
    }
}
