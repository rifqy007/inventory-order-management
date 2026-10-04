<?php

/**
 * Front Controller aplikasi Inventory and Order Management.
 *
 * File ini menjadi pintu masuk utama seluruh request aplikasi.
 * Apache mengarahkan URL aplikasi ke public/index.php melalui
 * aturan rewrite pada file public/.htaccess.
 *
 * Front Controller menentukan Controller dan method yang dijalankan
 * berdasarkan HTTP method dan URL path.
 */

declare(strict_types=1);

use App\Support\Http;

const LOGIN_PATH = '/login';

/**
 * Mengambil HTTP method dari request.
 */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/**
 * Mengambil URL path tanpa query string.
 *
 * Contoh:
 * /products?q=keyboard
 *
 * Menjadi:
 * /products
 */
$path = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

/**
 * Memastikan path selalu berbentuk string.
 */
if (!is_string($path) || $path === '') {
    $path = '/';
}

$outputBufferLevel = ob_get_level();
ob_start();

try {
    /**
     * Memuat bootstrap aplikasi di dalam blok penanganan exception agar
     * kegagalan koneksi/configuration juga mendapat response yang aman.
     */
    $container = require_once dirname(__DIR__) . '/config/bootstrap.php';

    /**
     * ================================================================
     * HALAMAN UTAMA
     * ================================================================
     */
    if ($path === '/') {
        Http::redirect(
            Http::user() !== null
                ? '/dashboard'
                : LOGIN_PATH
        );
    }

    /**
     * ================================================================
     * AUTENTIKASI
     * ================================================================
     */

    /**
     * Menampilkan form login.
     *
     * GET /login
     */
    if (
        $method === 'GET'
        && $path === LOGIN_PATH
    ) {
        $container['auth']->form();

        /**
         * Memproses login.
         *
         * POST /login
         */
    } elseif (
        $method === 'POST'
        && $path === LOGIN_PATH
    ) {
        $container['auth']->login();

        /**
         * Memproses logout.
         *
         * POST /logout
         */
    } elseif (
        $method === 'POST'
        && $path === '/logout'
    ) {
        $container['auth']->logout();

        /**
         * ================================================================
         * DASHBOARD
         * ================================================================
         */

        /**
         * Menampilkan dashboard sesuai role pengguna.
         *
         * GET /dashboard
         */
    } elseif (
        $method === 'GET'
        && $path === '/dashboard'
    ) {
        $container['dashboard']->index();

        /**
         * ================================================================
         * PRODUK
         * ================================================================
         */

        /**
         * Menampilkan daftar produk.
         *
         * GET /products
         *
         * Query parameter yang didukung:
         * - q
         * - direction
         * - per_page
         * - page
         */
    } elseif (
        $method === 'GET'
        && $path === '/products'
    ) {
        $container['master']->products();
    } elseif ($method === 'GET' && $path === '/products/new') {
        $container['master']->productForm();
    } elseif ($method === 'POST' && $path === '/products') {
        $container['master']->saveProduct();
    } elseif ($method === 'GET' && preg_match('#^/products/(\d+)/edit$#', $path, $matches) === 1) {
        $container['master']->productForm((int) $matches[1]);
    } elseif ($method === 'GET' && preg_match('#^/products/(\d+)$#', $path, $matches) === 1) {
        $container['master']->productDetail((int) $matches[1]);
    } elseif ($method === 'POST' && preg_match('#^/products/(\d+)$#', $path, $matches) === 1) {
        $container['master']->saveProduct((int) $matches[1]);
    } elseif ($method === 'POST' && preg_match('#^/products/(\d+)/status$#', $path, $matches) === 1) {
        $container['master']->setProductStatus((int) $matches[1]);

        /**
         * ================================================================
         * DAFTAR MASTER DATA
         * ================================================================
         *
         * Route yang ditangani:
         * - GET /categories
         * - GET /warehouses
         * - GET /suppliers
         * - GET /customers
         * - GET /users
         */
    } elseif (
        $method === 'GET'
        && preg_match(
            '#^/(categories|warehouses|suppliers|customers|users)$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['master']->list(
            $matches[1]
        );

        /**
         * ================================================================
         * TAMBAH MASTER DATA
         * ================================================================
         *
         * Route yang ditangani:
         * - POST /categories
         * - POST /warehouses
         * - POST /suppliers
         * - POST /customers
         */
    } elseif (
        $method === 'POST'
        && preg_match(
            '#^/(categories|warehouses|suppliers|customers)$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['master']->create(
            $matches[1]
        );

        /**
         * ================================================================
         * FORM EDIT MASTER DATA
         * ================================================================
         *
         * Contoh:
         * GET /suppliers/2/edit
         */
    } elseif (
        $method === 'GET'
        && preg_match(
            '#^/(categories|warehouses|suppliers|customers)/(\d+)/edit$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['master']->edit(
            $matches[1],
            (int) $matches[2]
        );

        /**
         * ================================================================
         * SIMPAN PERUBAHAN MASTER DATA
         * ================================================================
         *
         * Contoh:
         * POST /suppliers/2
         */
    } elseif (
        $method === 'POST'
        && preg_match(
            '#^/(categories|warehouses|suppliers|customers)/(\d+)$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['master']->update(
            $matches[1],
            (int) $matches[2]
        );

        /**
         * ================================================================
         * AKTIFKAN ATAU NONAKTIFKAN MASTER DATA
         * ================================================================
         *
         * Contoh:
         * POST /suppliers/2/status
         */
    } elseif (
        $method === 'POST'
        && preg_match(
            '#^/(categories|warehouses|suppliers|customers)/(\d+)/status$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['master']->changeStatus(
            $matches[1],
            (int) $matches[2]
        );

        /**
         * ================================================================
         * INVENTORY DAN PERGERAKAN STOK
         * ================================================================
         */

        /**
         * Menampilkan form pergerakan stok.
         *
         * GET /inventory/move
         */
    } elseif (
        $method === 'GET'
        && $path === '/inventory/move'
    ) {
        $container['inventory']->form();

        /**
         * Memproses pergerakan stok.
         *
         * POST /inventory/move
         */
    } elseif (
        $method === 'POST'
        && $path === '/inventory/move'
    ) {
        $container['inventory']->move();

        /**
         * ================================================================
         * PURCHASE ORDER DASAR
         * ================================================================
         */

        /**
         * Menampilkan daftar Purchase Order.
         *
         * GET /purchase-orders
         */
    } elseif (
        $method === 'GET'
        && $path === '/purchase-orders'
    ) {
        $container['order']->list('po');

        /**
         * ================================================================
         * SALES ORDER DASAR
         * ================================================================
         */

        /**
         * Menampilkan daftar Sales Order.
         *
         * GET /sales-orders
         */
    } elseif (
        $method === 'GET'
        && $path === '/sales-orders'
    ) {
        $container['order']->list('so');

        /**
         * Menyetujui Sales Order melalui OrderController.
         *
         * POST /sales-orders/1/approve
         */
    } elseif (
        $method === 'POST'
        && preg_match(
            '#^/sales-orders/(\d+)/approve$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['order']->approve(
            (int) $matches[1]
        );

        /**
         * ================================================================
         * WORKFLOW SALES ORDER
         * ================================================================
         */

        /**
         * Menampilkan daftar Sales Order workflow.
         *
         * GET /sales-orders/workflow
         */
    } elseif (
        $method === 'GET'
        && $path === '/sales-orders/workflow'
    ) {
        $container['workflow']->salesList();
    } elseif ($method === 'GET' && $path === '/sales-orders/workflow/new') {
        $container['workflow']->salesCreateForm();
    } elseif ($method === 'POST' && $path === '/sales-orders/workflow') {
        $container['workflow']->salesCreate();

        /**
         * Menampilkan detail Sales Order workflow.
         *
         * GET /sales-orders/workflow/1
         */
    } elseif (
        $method === 'GET'
        && preg_match(
            '#^/sales-orders/workflow/(\d+)$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['workflow']->salesDetail(
            (int) $matches[1]
        );

        /**
         * Memproses perubahan status Sales Order.
         *
         * Action yang diperbolehkan:
         * - submit;
         * - approve;
         * - reject;
         * - fulfill.
         *
         * Contoh:
         * POST /sales-orders/workflow/1/submit
         */
    } elseif (
        $method === 'POST'
        && preg_match(
            '#^/sales-orders/workflow/(\d+)/(submit|approve|reject|fulfill|cancel)$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['workflow']->salesAction(
            (int) $matches[1],
            $matches[2]
        );

        /**
         * ================================================================
         * WORKFLOW PURCHASE ORDER
         * ================================================================
         */

        /**
         * Menampilkan daftar Purchase Order workflow.
         *
         * GET /purchase-orders/workflow
         */
    } elseif (
        $method === 'GET'
        && $path === '/purchase-orders/workflow'
    ) {
        $container['workflow']->purchaseList();
    } elseif ($method === 'GET' && $path === '/purchase-orders/workflow/new') {
        $container['workflow']->purchaseCreateForm();
    } elseif ($method === 'POST' && $path === '/purchase-orders/workflow') {
        $container['workflow']->purchaseCreate();

        /**
         * Menampilkan detail Purchase Order workflow.
         *
         * GET /purchase-orders/workflow/1
         */
    } elseif (
        $method === 'GET'
        && preg_match(
            '#^/purchase-orders/workflow/(\d+)$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['workflow']->purchaseDetail(
            (int) $matches[1]
        );

        /**
         * Mengubah Purchase Order dari Draft menjadi Ordered.
         *
         * POST /purchase-orders/workflow/1/order
         */
    } elseif (
        $method === 'POST'
        && preg_match(
            '#^/purchase-orders/workflow/(\d+)/order$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['workflow']->purchaseOrder(
            (int) $matches[1]
        );
    } elseif ($method === 'POST' && preg_match('#^/purchase-orders/workflow/(\d+)/cancel$#', $path, $matches) === 1) {
        $container['workflow']->cancelPurchaseOrder((int) $matches[1]);

        /**
         * Memproses penerimaan barang.
         *
         * POST /purchase-orders/workflow/1/receive
         */
    } elseif (
        $method === 'POST'
        && preg_match(
            '#^/purchase-orders/workflow/(\d+)/receive$#',
            $path,
            $matches
        ) === 1
    ) {
        $container['workflow']->receive(
            (int) $matches[1]
        );

        /**
         * ================================================================
         * STOCK LEDGER DAN LAPORAN
         * ================================================================
         */

        /**
         * Menampilkan Stock Ledger.
         *
         * GET /stock-ledger
         */
    } elseif (
        $method === 'GET'
        && $path === '/stock-ledger'
    ) {
        $container['workflow']->ledger();

        /**
         * Mengunduh Stock Ledger dalam format CSV.
         *
         * GET /reports/stock-ledger.csv
         */
    } elseif (
        $method === 'GET'
        && $path === '/reports/stock-ledger.csv'
    ) {
        $container['workflow']->ledgerCsv();
    } elseif ($method === 'GET' && $path === '/reports/order-status.csv') {
        $container['workflow']->orderCsv();

        /**
         * ================================================================
         * JSON API KETERSEDIAAN PRODUK
         * ================================================================
         *
         * Contoh:
         * GET /api/products/SKU-001/availability
         */
    } elseif ($method === 'GET' && $path === '/admin/users') {
        $container['adminUser']->index();
    } elseif ($method === 'GET' && $path === '/admin/users/create') {
        $container['adminUser']->form();
    } elseif ($method === 'POST' && $path === '/admin/users') {
        $container['adminUser']->save();
    } elseif (
        $method === 'GET'
        && preg_match('#^/admin/users/(\d+)/edit$#', $path, $matches) === 1
    ) {
        $container['adminUser']->form((int) $matches[1]);
    } elseif (
        $method === 'POST'
        && preg_match('#^/admin/users/(\d+)$#', $path, $matches) === 1
    ) {
        $container['adminUser']->save((int) $matches[1]);
    } elseif (
        $method === 'POST'
        && preg_match('#^/admin/users/(\d+)/status$#', $path, $matches) === 1
    ) {
        $container['adminUser']->changeStatus((int) $matches[1]);
    } elseif (
        $method === 'POST'
        && preg_match('#^/admin/users/(\d+)/reset-password$#', $path, $matches) === 1
    ) {
        $container['adminUser']->resetPassword((int) $matches[1]);
    } elseif (
        $method === 'GET'
        && preg_match(
            '#^/api/products/([^/]+)/availability$#',
            $path,
            $matches
        ) === 1
    ) {
        header('Content-Type: application/json; charset=utf-8');
        if (Http::user() === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Autentikasi diperlukan.']);
            exit;
        }
        $statement = $container['repo']->pdo()->prepare(
            'SELECT p.sku, w.name AS warehouse, COALESCE(ps.quantity, 0) AS quantity
             FROM products AS p
             CROSS JOIN warehouses AS w
             LEFT JOIN product_stocks AS ps
                ON ps.product_id = p.id AND ps.warehouse_id = w.id
             WHERE p.sku = :sku
             ORDER BY w.name ASC'
        );

        $statement->execute([
            'sku' => $matches[1],
        ]);

        $availability = $statement->fetchAll();

        http_response_code(
            $availability !== []
                ? 200
                : 404
        );

        echo json_encode(
            $availability !== []
                ? $availability
                : [
                    'error' => 'SKU tidak ditemukan',
                ],
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        /**
         * ================================================================
         * HALAMAN TIDAK DITEMUKAN
         * ================================================================
         */
    } elseif (str_starts_with($path, '/api/')) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'Endpoint tidak ditemukan.'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    } else {
        http_response_code(404);

        Http::view('errors/404');
    }

    ob_end_flush();
} catch (Throwable $exception) {
    while (ob_get_level() > $outputBufferLevel) {
        ob_end_clean();
    }

    /**
     * Detail error hanya ditulis ke log.
     *
     * Informasi teknis tidak ditampilkan kepada pengguna untuk
     * mencegah kebocoran informasi internal aplikasi.
     */
    error_log(
        (string) $exception
    );

    http_response_code(500);

    if (str_starts_with($path, '/api/')) {
        header('Content-Type: application/json; charset=utf-8', true);
        echo json_encode(
            ['error' => 'Terjadi kesalahan internal.'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    } elseif (class_exists(Http::class)) {
        header('Content-Type: text/html; charset=utf-8', true);
        try {
            Http::view('errors/500');
        } catch (Throwable $renderException) {
            error_log((string) $renderException);
            header('Content-Type: text/html; charset=utf-8', true);
            echo '<!doctype html><html lang="id"><meta charset="utf-8">'
                . '<title>Kesalahan Internal</title><h1>500</h1>'
                . '<p>Aplikasi tidak dapat menyelesaikan permintaan saat ini.</p>'
                . '</html>';
        }
    } else {
        header('Content-Type: text/html; charset=utf-8', true);
        echo '<!doctype html><html lang="id"><meta charset="utf-8">'
            . '<title>Kesalahan Internal</title><h1>500</h1>'
            . '<p>Aplikasi tidak dapat menyelesaikan permintaan saat ini.</p>'
            . '</html>';
    }
}
