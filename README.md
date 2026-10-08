# Inventory & Order Management System

Aplikasi web pengelolaan inventori dan pesanan berbasis PHP Native, MySQL, dan Docker Compose. Aplikasi mendukung tiga role—Admin, Sales, dan Warehouse Staff—serta stok multi-gudang.

## Persyaratan

- Git
- Docker Desktop dengan Docker Compose v2 aktif
- Koneksi internet saat pertama kali membangun image dan memasang dependency

PHP dan MySQL dijalankan di container; keduanya tidak perlu dipasang terpisah di komputer host.

## Menjalankan project

### 1. Clone repository

Jalankan perintah berikut di PowerShell:

```powershell
git clone https://github.com/rifqy007/inventory-order-management.git
cd inventory-order-management
```

Jika source sudah tersedia di komputer, buka terminal di folder project dan lanjutkan ke langkah berikutnya.

### 2. Siapkan konfigurasi lokal

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

`.env` berisi konfigurasi untuk komputer lokal dan tidak boleh di-commit. Untuk penggunaan lokal biasa, nilai bawaan pada `.env.example` dapat digunakan. Ganti password contoh sebelum menjalankan aplikasi pada jaringan bersama atau server yang dapat diakses orang lain.

### 3. Bangun dan jalankan container

```powershell
docker compose up --build -d
docker compose exec app composer install --no-interaction
docker compose ps
```

Compose menjalankan aplikasi Apache/PHP, database MySQL aplikasi, dan database MySQL terpisah untuk integration test. Pada inisialisasi volume database yang masih kosong, schema, migration, dan data demo dimuat otomatis. Supplier terhubung ke satu kategori produk; saat membuat Purchase Order, pilihan produk dibatasi sesuai kategori supplier.

Jika database lokal sudah berjalan sebelum fitur kategori supplier ditambahkan, jalankan migration `database/migrations/005-supplier-product-category.sql` satu kali pada database `inventory` (misalnya melalui phpMyAdmin/Workbench). Migration ini menambahkan kategori **Konsumsi** bila belum ada dan memberi kategori **ATK** sebagai nilai awal untuk supplier lama. Volume Docker yang sudah terinisialisasi tidak menjalankan ulang berkas init secara otomatis.

Buka **http://localhost:8080**. Jika port 8080 sudah digunakan, ubah `APP_PORT` di `.env`. Port database host dapat diubah melalui `DB_HOST_PORT`.

### 4. Masuk menggunakan akun demo

Semua akun demo menggunakan password `Password123!`:

| Role            | Email                                                  |
| --------------- | ------------------------------------------------------ |
| Admin           | `admin@example.com`                                    |
| Sales           | `sales1@example.com` atau `sales2@example.com`         |
| Warehouse Staff | `warehouse1@example.com` atau `warehouse2@example.com` |

Akun dan password ini hanya untuk demo lokal. Jangan gunakan kredensial demo di deployment publik.

## Alur penggunaan singkat

1. Masuk sebagai Admin untuk memeriksa user dan master data: produk, kategori, gudang, supplier, dan customer.
2. Masuk sebagai Sales, buat Sales Order berstatus Draft, lalu ajukan untuk persetujuan.
3. Masuk sebagai Admin untuk menyetujui atau menolak Sales Order. Sales tidak memiliki kewenangan untuk menyetujui order.
4. Masuk sebagai Warehouse Staff untuk memproses goods issue dari Sales Order yang sudah disetujui.
5. Untuk pembelian, Admin atau Warehouse Staff membuat Purchase Order; Warehouse Staff dapat mencatat penerimaan sebagian maupun penuh.
6. Periksa perubahan stok melalui Stock Ledger, dashboard, dan laporan.

Perubahan stok dari goods receipt dan goods issue dicatat bersama pembaruan stok dalam transaksi database. Stok dikunci saat diproses untuk mencegah oversell pada operasi yang bersamaan.

## Fitur

- Login, logout, session, CSRF protection, dan otorisasi server-side untuk tiga role.
- Manajemen user serta master produk, kategori, gudang, supplier, dan customer.
- Upload gambar produk dengan validasi tipe dan ukuran file.
- Stok multi-gudang, pencarian, filter, sorting, pagination, dan halaman detail.
- Alur Purchase Order dan Sales Order, termasuk approval, penerimaan sebagian, goods issue, dan stock ledger.
- Dashboard sesuai role dan ekspor CSV untuk ledger serta status order.
- Endpoint JSON ketersediaan produk: `/api/products/{SKU}/availability`, dipanggil menggunakan Fetch API pada form Sales Order. Endpoint memerlukan login.
- Script pemeriksaan stok rendah: `scripts/check-low-stock.php`.

## Pemeriksaan kualitas

Container `db-test` digunakan oleh integration test sehingga database demo `db` tidak dipakai untuk test. Jalankan seluruh pemeriksaan dari root project:

```powershell
docker compose exec app composer quality
```

Perintah tersebut menjalankan unit test, integration test, PHPStan, dan PHP_CodeSniffer. Untuk menjalankan masing-masing pemeriksaan:

```powershell
docker compose exec app composer test
docker compose exec app composer test:integration
docker compose exec app composer analyse
docker compose exec app composer format-check
```

Script low-stock dapat dijalankan manual:

```powershell
docker compose exec app php scripts/check-low-stock.php
```

## Menghentikan dan mereset aplikasi

Untuk menghentikan container tanpa menghapus data database:

```powershell
docker compose down
```

Untuk menghapus seluruh data demo dan membuat ulang database dari schema serta seed:

```powershell
docker compose down -v
docker compose up --build -d
docker compose exec app composer install --no-interaction
```

**Perhatian:** `docker compose down -v` menghapus data pada volume database aplikasi dan database test. Gunakan hanya jika memang ingin reset data.

## Dokumentasi project

- `docs/planning/`: scope, user stories, ERD, dan diagram rancangan.
- `docs/architecture/`: diagram as-built dan Architecture Decision Records.
- `docs/quality/`: critique, refactoring log, static analysis, dan tech debt.
- `docs/testing/`: skenario, hasil verifikasi, dan [checklist submission](docs/testing/submission-checklist.md).
- `docs/presentation/`: panduan presentasi project selama 10 menit.
- `ai-usage-log.md`: catatan penggunaan AI selama pengerjaan.

Selesaikan checklist submission dari salinan final yang bersih sebelum submission. Hasil pengujian yang tersimpan di dokumentasi hanya menggambarkan tanggal dan kondisi source saat pengujian tersebut dilakukan.

## Analisis SonarQube (opsional)

Konfigurasi SonarQube bukan syarat untuk menjalankan aplikasi. Untuk scan lokal di Windows, jalankan SonarQube dan Docker Desktop, lalu dari root project:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
./scripts/sonar-scan.ps1
```

Skrip akan menjalankan unit dan integration test, membuat laporan coverage, lalu meminta token SonarQube secara tersembunyi. `SONAR_HOST_URL` dan `SONAR_PROJECT_KEY` dapat diatur di `.env` atau diberikan sebagai parameter skrip. Jangan menaruh token di `.env`, source, atau repository.

### Catatan hasil scan

Snapshot **Overall Code** dari dashboard SonarQube pada 7 Oktober 2026:

| Metrik | Hasil |
| --- | ---: |
| Security | B — 1 open issue |
| Reliability | B — 1 open issue |
| Maintainability | A — 9 open issues |
| Accepted issues | 0 |
| Coverage | 45.0% pada sekitar 1.7k lines to cover |
| Duplications | 2.4% pada sekitar 5.5k lines |
| Security Hotspots | A — 0 |

Pada pemindaian 8 Oktober 2026, PHPUnit melaporkan **24 test lulus dengan 83 assertion**. Analisis berhasil diunggah, tetapi Quality Gate **gagal** karena coverage New Code **21,8%**, di bawah ambang **80%**. Hasil ini perlu diperbarui setelah coverage New Code diperbaiki dan scan dijalankan ulang.

Pemindaian SonarQube di project ini dijalankan manual dari komputer lokal. Konfigurasi dan skripnya disimpan di `sonar-project.properties` serta `scripts/sonar-scan.ps1`; token diminta saat skrip berjalan dan tidak disimpan dalam repository. CI/CD tidak diperlukan oleh project brief.
