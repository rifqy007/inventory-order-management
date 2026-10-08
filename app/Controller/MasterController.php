<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\MysqlInventoryRepository;
use App\Exception\ProductImageStorageFailure;
use App\Support\Http;
use App\Support\ListOptions;
use Throwable;

/** Menangani halaman master data dan aksi khusus Admin. */
final class MasterController
{
    private const NOT_FOUND_VIEW = 'errors/404';
    private const PRODUCTS_BASE_PATH = '/products';
    private const PRODUCT_CREATE_PATH = self::PRODUCTS_BASE_PATH . '/new';
    private const PRODUCT_PATH = self::PRODUCTS_BASE_PATH . '/';
    private const EDIT_PATH = '/edit';
    private const RETURN_TO_QUERY = '?return_to=';

    public function __construct(private MysqlInventoryRepository $repository)
    {
    }

    public function products(): void
    {
        Http::requireLogin();
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'direction' => (string) ($_GET['direction'] ?? 'asc'),
            'sort' => (string) ($_GET['sort'] ?? 'name'),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'stock' => (string) ($_GET['stock'] ?? ''),
            'active' => (string) ($_GET['active'] ?? ''),
        ];
        $options = ListOptions::fromRequest($filters, ['sku', 'name', 'category', 'unit', 'selling_price', 'total_stock', 'warehouse_stocks', 'reorder_point'], 'name');
        $filters['page'] = $options->page;
        $filters['per_page'] = $options->perPage;
        $filters['direction'] = $options->direction;
        $filters['sort'] = $options->sort;
        $total = $this->repository->productCount($filters);
        $filters['page'] = min($filters['page'], max(1, (int) ceil($total / $filters['per_page'])));
        Http::view('products/index', [
            'products' => $this->repository->products($filters),
            'categories' => $this->repository->activeOptions('categories'),
            'filters' => $filters,
            'total' => $total,
        ]);
    }

    public function productForm(?int $id = null): void
    {
        Http::requireRole(['Admin']);
        $product = $id === null ? null : $this->repository->findProduct($id);
        if ($id !== null && $product === null) {
            http_response_code(404);
            Http::view(self::NOT_FOUND_VIEW);
            return;
        }
        Http::view('products/form', [
            'product' => $product,
            'categories' => $this->repository->activeOptions('categories'),
            'returnTo' => Http::returnTo(self::PRODUCTS_BASE_PATH),
        ]);
    }

    public function productDetail(int $id): void
    {
        Http::requireLogin();
        $product = $this->repository->productDetail($id);
        if ($product === null) {
            http_response_code(404);
            Http::view(self::NOT_FOUND_VIEW);
            return;
        }
        Http::view('products/detail', [
            'product' => $product,
            'returnTo' => Http::returnTo(self::PRODUCTS_BASE_PATH),
        ]);
    }

    public function saveProduct(?int $id = null): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo(self::PRODUCTS_BASE_PATH);
        $data = [
            'sku' => strtoupper(trim((string) ($_POST['sku'] ?? ''))),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'unit' => trim((string) ($_POST['unit'] ?? '')),
            'purchase_price' => $this->parseRupiahInput($_POST['purchase_price'] ?? null),
            'selling_price' => $this->parseRupiahInput($_POST['selling_price'] ?? null),
            'reorder_point' => filter_var($_POST['reorder_point'] ?? null, FILTER_VALIDATE_INT),
        ];
        if (
            $data['sku'] === '' || $data['name'] === '' || $data['unit'] === ''
            || $data['category_id'] < 1 || $data['purchase_price'] === false
            || $data['selling_price'] === false || $data['reorder_point'] === false
            || $data['purchase_price'] < 0 || $data['selling_price'] < 0
            || $data['reorder_point'] < 0
        ) {
            Http::flash('error', 'Lengkapi data produk. Harga dan reorder point harus bernilai nol atau lebih.');
            Http::redirect(($id === null ? self::PRODUCT_CREATE_PATH : self::PRODUCT_PATH . $id . self::EDIT_PATH) . self::RETURN_TO_QUERY . rawurlencode($returnTo));
        }
        try {
            $data['image_path'] = $this->storeProductImage();
            if ($id === null) {
                $this->repository->createProduct($data);
            } else {
                $this->repository->updateProduct($id, $data);
            }
            Http::flash('success', 'Data produk berhasil disimpan.');
            Http::redirect($returnTo);
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
            Http::redirect(($id === null ? self::PRODUCT_CREATE_PATH : self::PRODUCT_PATH . $id . self::EDIT_PATH) . self::RETURN_TO_QUERY . rawurlencode($returnTo));
        } catch (Throwable) {
            Http::flash('error', 'SKU mungkin sudah digunakan atau data produk tidak valid.');
            Http::redirect(($id === null ? self::PRODUCT_CREATE_PATH : self::PRODUCT_PATH . $id . self::EDIT_PATH) . self::RETURN_TO_QUERY . rawurlencode($returnTo));
        }
    }

    public function setProductStatus(int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo(self::PRODUCTS_BASE_PATH);
        $active = (string) ($_POST['active'] ?? '0') === '1';
        $this->repository->setProductActive($id, $active);
        Http::flash('success', $active ? 'Produk diaktifkan.' : 'Produk dinonaktifkan.');
        Http::redirect($returnTo);
    }

    public function list(string $table): void
    {
        Http::requireRole(['Admin']);
        if ($table === 'users') {
            Http::redirect('/admin/users');
        }

        $allowedSorts = match ($table) {
            'categories' => ['name', 'description', 'is_active'],
            'warehouses' => ['name', 'location', 'is_active'],
            'suppliers' => ['name', 'category', 'contact', 'address', 'is_active'],
            'customers' => ['name', 'contact', 'address', 'is_active'],
            default => throw new \InvalidArgumentException('Master data tidak diizinkan.'),
        };
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'direction' => (string) ($_GET['direction'] ?? 'asc'),
            'sort' => (string) ($_GET['sort'] ?? 'name'),
        ];
        $options = ListOptions::fromRequest($filters, $allowedSorts, 'name');
        $filters['page'] = $options->page;
        $filters['per_page'] = $options->perPage;
        $filters['direction'] = $options->direction;
        $filters['sort'] = $options->sort;
        $total = $this->repository->masterCount($table, $filters['q']);
        $pages = max(1, (int) ceil($total / $filters['per_page']));
        $filters['page'] = min($filters['page'], $pages);

        Http::view('master/index', [
            'table' => $table,
            'rows' => $this->repository->masterRows($table, $filters),
            'filters' => $filters,
            'total' => $total,
            'pages' => $pages,
            'categories' => $table === 'suppliers' ? $this->repository->activeOptions('categories') : [],
        ]);
    }

    public function create(string $table): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo('/' . $table);

        $data = $this->masterInput($table);
        if ($data['name'] === '' || !$this->validSupplierCategory($table, $data)) {
            Http::flash('error', $data['name'] === '' ? 'Nama wajib diisi.' : 'Pilih kategori produk yang aktif untuk supplier.');
            Http::redirect($returnTo);
        }

        try {
            $this->repository->insertMaster($table, $data);
            Http::flash('success', 'Data berhasil ditambahkan.');
        } catch (Throwable) {
            Http::flash('error', 'Data tidak dapat disimpan. Pastikan nama belum digunakan.');
        }
        Http::redirect($returnTo);
    }

    public function edit(string $table, int $id): void
    {
        Http::requireRole(['Admin']);
        $row = $this->repository->findMasterById($table, $id);

        if ($row === null) {
            http_response_code(404);
            Http::view(self::NOT_FOUND_VIEW);
            return;
        }

        Http::view('master/edit', [
            'table' => $table,
            'row' => $row,
            'returnTo' => Http::returnTo('/' . $table),
            'categories' => $table === 'suppliers' ? $this->repository->activeOptions('categories') : [],
        ]);
    }

    public function update(string $table, int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo('/' . $table);

        $data = $this->masterInput($table);
        if ($data['name'] === '' || !$this->validSupplierCategory($table, $data)) {
            Http::flash('error', $data['name'] === '' ? 'Nama wajib diisi.' : 'Pilih kategori produk yang aktif untuk supplier.');
            Http::redirect('/' . $table . '/' . $id . '/edit?return_to=' . rawurlencode($returnTo));
        }

        try {
            $this->repository->updateMaster($table, $id, $data);
            Http::flash('success', 'Data berhasil diperbarui.');
        } catch (Throwable) {
            Http::flash('error', 'Data tidak dapat diperbarui. Pastikan nama belum digunakan.');
        }
        Http::redirect($returnTo);
    }

    public function changeStatus(string $table, int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $returnTo = Http::returnTo('/' . $table);

        $active = ((string) ($_POST['active'] ?? '0')) === '1';
        $this->repository->setMasterActive($table, $id, $active);

        Http::flash(
            'success',
            $active ? 'Data berhasil diaktifkan.' : 'Data berhasil dinonaktifkan.'
        );
        Http::redirect($returnTo);
    }

    private function masterInput(string $table): array
    {
        $common = ['name' => trim((string) ($_POST['name'] ?? ''))];

        return match ($table) {
            'categories' => $common + [
                'description' => trim((string) ($_POST['description'] ?? '')),
            ],
            'warehouses' => $common + [
                'location' => trim((string) ($_POST['location'] ?? '')),
            ],
            'suppliers' => $common + [
                'category_id' => (int) ($_POST['category_id'] ?? 0),
                'contact' => trim((string) ($_POST['contact'] ?? '')),
                'address' => trim((string) ($_POST['address'] ?? '')),
            ],
            'customers' => $common + [
                'contact' => trim((string) ($_POST['contact'] ?? '')),
                'address' => trim((string) ($_POST['address'] ?? '')),
            ],
            default => throw new \InvalidArgumentException('Master data tidak diizinkan.'),
        };
    }

    private function validSupplierCategory(string $table, array $data): bool
    {
        if ($table !== 'suppliers') {
            return true;
        }
        foreach ($this->repository->activeOptions('categories') as $category) {
            if ((int) $category['id'] === (int) ($data['category_id'] ?? 0)) {
                return true;
            }
        }
        return false;
    }

    private function storeProductImage(): ?string
    {
        $upload = $_FILES['image'] ?? null;
        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (
            (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || (int) ($upload['size'] ?? 0) > 2 * 1024 * 1024
            || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))
        ) {
            throw new \DomainException('Gambar tidak valid atau ukurannya lebih dari 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mime])) {
            throw new \DomainException('Format gambar harus JPEG, PNG, atau WebP.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $directory = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new ProductImageStorageFailure('Folder upload tidak dapat dibuat.');
        }
        if (!move_uploaded_file((string) $upload['tmp_name'], $directory . '/' . $name)) {
            throw new ProductImageStorageFailure('Gambar tidak dapat disimpan.');
        }
        return '/uploads/' . $name;
    }

    private function parseRupiahInput(mixed $value): float|false
    {
        $formatted = trim((string) $value);
        if ($formatted === '' || preg_match('/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/D', $formatted) !== 1) {
            return false;
        }

        $normalized = str_replace(',', '.', str_replace('.', '', $formatted));
        $amount = filter_var($normalized, FILTER_VALIDATE_FLOAT);
        return $amount === false ? false : (float) $amount;
    }
}
