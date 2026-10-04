<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\InventoryRepositoryInterface;
use App\Support\Http;

final class DashboardController
{
    public function __construct(private InventoryRepositoryInterface $repo)
    {
    }
    public function index(): void
    {
        $u = Http::requireLogin();
        Http::view('dashboard', ['metrics' => $this->repo->dashboard($u['role'], $u['id'])]);
    }
}
