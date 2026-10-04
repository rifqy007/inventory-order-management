# Refactoring Log

Log ini mencatat tiga refactor beserta smell, teknik, dan cuplikan sebelum/sesudah.

**Batas bukti:** riwayat commit lama belum dapat diverifikasi dari environment ini, sehingga cuplikan *before* di bawah direkonstruksi dari catatan perubahan dan bukan salinan yang dapat diverifikasi dari commit lama. Cuplikan *after* berasal dari source saat ini. Jangan menyajikan rekonstruksi sebagai bukti historis yang terverifikasi.

## 1. Isolasi transaksi inventori

- **Smell:** dependency inversion violation; business service mengetahui PDO.
- **Teknik:** Extract Repository Boundary dan constructor injection.
- **Before (rekonstruksi):**

  ```php
  $this->pdo->beginTransaction();
  // update ProductStock dan insert StockLedger
  $this->pdo->commit();
  ```

- **After (source saat ini):**

  ```php
  $this->repo->beginTransaction();
  $this->repo->setStock($productId, $warehouseId, $after);
  $this->repo->addLedger([
      'product_id' => $productId,
      'warehouse_id' => $warehouseId,
      'movement_type' => $type,
      // metadata referensi dan kuantitas sebelum/sesudah
  ]);
  $this->repo->commit();
  ```

  `InventoryService` menerima `InventoryRepositoryInterface`; unit test menggunakan `FakeInventoryRepository`.

## 2. Pilihan dan pagination daftar order

- **Smell:** daftar awal mengambil semua order dan filter/pagination belum menjadi input yang terstruktur.
- **Teknik:** Introduce Parameter Object melalui `ListOptions`; gunakan allowlist untuk sort dan batasi hasil query.
- **Before (rekonstruksi):**

  ```php
  $rows = $this->repo->all($table);
  ```

- **After (source saat ini):**

  ```php
  $options = ListOptions::fromRequest(
      $filters,
      array_keys($definition['sort_columns']),
      'order_date'
  );
  $offset = $options->offset();
  // Query menggunakan sort allowlist, LIMIT $options->perPage, dan OFFSET $offset.
  ```

  Workflow order kini mendukung search, status, sort, dan pagination. Route daftar legacy tetap aktif dan dicatat sebagai tech debt karena masih memakai daftar sederhana.

## 3. Keamanan ekspor CSV

- **Smell:** untrusted database text dapat ditafsirkan spreadsheet sebagai formula.
- **Teknik:** Extract Method (`safeCsvCell`) dan gunakan pada setiap nilai laporan.
- **Before (rekonstruksi):**

  ```php
  fputcsv($out, array_values($row));
  ```

- **After (source saat ini):**

  ```php
  fputcsv($out, array_map([$this, 'safeCsvCell'], array_values($row)));
  ```

  Nilai yang diawali karakter formula diberi prefix teks sebelum ditulis.

## Audit SRP

`public/index.php` masih mencocokkan route, menyiapkan response API, dan mengambil data availability melalui repository/PDO. Beberapa controller juga memakai repository MySQL atau PDO langsung. Perubahan kontrak API atau storage masih dapat memengaruhi controller/front controller; pemisahan endpoint ke controller/service khusus menjadi arah refactor berikutnya.

## Catatan Git

Metadata `.git` tersedia, tetapi status dan riwayat commit tidak dapat diperiksa dari environment ini karena Git menolak ownership repository. Cuplikan before tetap merupakan rekonstruksi; verifikasi dari akun pemilik project sebelum submission. Buat commit berdasarkan perubahan nyata dan jangan merekayasa riwayat lama.
