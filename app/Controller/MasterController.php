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
    private const PRODUCT_CREATE_PATH = '/products/new';
    private const PRODUCT_PATH = '/products/';
    private const EDIT_PATH = '/edit';

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
        Http::view('products/detail', ['product' => $product]);
    }

    public function saveProduct(?int $id = null): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $data = [
            'sku' => strtoupper(trim((string) ($_POST['sku'] ?? ''))),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'unit' => trim((string) ($_POST['unit'] ?? '')),
            'purchase_price' => filter_var($_POST['purchase_price'] ?? null, FILTER_VALIDATE_FLOAT),
            'selling_price' => filter_var($_POST['selling_price'] ?? null, FILTER_VALIDATE_FLOAT),
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
            Http::redirect($id === null ? self::PRODUCT_CREATE_PATH : self::PRODUCT_PATH . $id . self::EDIT_PATH);
        }
        try {
            $data['image_path'] = $this->storeProductImage();
            if ($id === null) {
                $this->repository->createProduct($data);
            } else {
                $this->repository->updateProduct($id, $data);
            }
            Http::flash('success', 'Data produk berhasil disimpan.');
            Http::redirect('/products');
        } catch (\DomainException $e) {
            Http::flash('error', $e->getMessage());
            Http::redirect($id === null ? self::PRODUCT_CREATE_PATH : self::PRODUCT_PATH . $id . self::EDIT_PATH);
        } catch (Throwable) {
            Http::flash('error', 'SKU mungkin sudah digunakan atau data produk tidak valid.');
            Http::redirect($id === null ? self::PRODUCT_CREATE_PATH : self::PRODUCT_PATH . $id . self::EDIT_PATH);
        }
    }

    public function setProductStatus(int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();
        $active = (string) ($_POST['active'] ?? '0') === '1';
        $this->repository->setProductActive($id, $active);
        Http::flash('success', $active ? 'Produk diaktifkan.' : 'Produk dinonaktifkan.');
        Http::redirect('/products');
    }

    public function list(string $table): void
    {
        Http::requireRole(['Admin']);
        Http::view('master/index', [
            'table' => $table,
            'rows' => $this->repository->all($table),
        ]);
    }

    public function create(string $table): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();

        $data = $this->masterInput($table);
        if ($data['name'] === '') {
            Http::flash('error', 'Nama wajib diisi.');
            Http::redirect('/' . $table);
        }

        try {
            $this->repository->insertMaster($table, $data);
            Http::flash('success', 'Data berhasil ditambahkan.');
        } catch (Throwable) {
            Http::flash('error', 'Data tidak dapat disimpan. Pastikan nama belum digunakan.');
        }
        Http::redirect('/' . $table);
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
        ]);
    }

    public function update(string $table, int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();

        $data = $this->masterInput($table);
        if ($data['name'] === '') {
            Http::flash('error', 'Nama wajib diisi.');
            Http::redirect('/' . $table . '/' . $id . '/edit');
        }

        try {
            $this->repository->updateMaster($table, $id, $data);
            Http::flash('success', 'Data berhasil diperbarui.');
        } catch (Throwable) {
            Http::flash('error', 'Data tidak dapat diperbarui. Pastikan nama belum digunakan.');
        }
        Http::redirect('/' . $table);
    }

    public function changeStatus(string $table, int $id): void
    {
        Http::requireRole(['Admin']);
        Http::verifyCsrf();

        $active = ((string) ($_POST['active'] ?? '0')) === '1';
        $this->repository->setMasterActive($table, $id, $active);

        Http::flash(
            'success',
            $active ? 'Data berhasil diaktifkan.' : 'Data berhasil dinonaktifkan.'
        );
        Http::redirect('/' . $table);
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
            'suppliers', 'customers' => $common + [
                'contact' => trim((string) ($_POST['contact'] ?? '')),
                'address' => trim((string) ($_POST['address'] ?? '')),
            ],
            default => throw new \InvalidArgumentException('Master data tidak diizinkan.'),
        };
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
}
