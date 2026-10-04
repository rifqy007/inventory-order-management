<?php

/**
 * Bootstrap adalah titik persiapan aplikasi.
 * File ini mengaktifkan autoload, session aman, koneksi database, repository,
 * service, dan controller. Dependency dibuat di satu tempat agar Service tidak
 * pernah membuat PDO sendiri dan mudah diganti dengan fake saat unit test.
 */

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Dependency belum diinstal. Jalankan: docker compose exec app composer install');
}
require_once $autoload;

use App\Controller\AdminUserController;
use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Controller\InventoryController;
use App\Controller\MasterController;
use App\Controller\OrderController;
use App\Controller\WorkflowController;
use App\Repository\MysqlInventoryRepository;
use App\Repository\MysqlOrderRepository;
use App\Service\AuthService;
use App\Service\InventoryService;
use App\Service\OrderService;
use App\Support\Database;

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Session cookies always use Secure; modern browsers allow this for localhost.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => true,
    ]);
    session_start();
}
$pdo = Database::connect();
$repo = new MysqlInventoryRepository($pdo);
$authService = new AuthService($repo);
$inventoryService = new InventoryService($repo);
$orderRepo = new MysqlOrderRepository($pdo);
$orderService = new OrderService($orderRepo);
return [
    'adminUser' => new AdminUserController($pdo),
    'workflow' => new WorkflowController($orderRepo, $orderService, $repo),
    'auth' => new AuthController($authService),
    'dashboard' => new DashboardController($repo),
    'master' => new MasterController($repo),
    'inventory' => new InventoryController($inventoryService, $repo),
    'order' => new OrderController($repo, $pdo),
    'repo' => $repo,
];
