# Hasil Verifikasi

## Verifikasi otomatis terbaru

Tanggal: **2026-10-05**. Dijalankan melalui `docker compose exec app composer quality` dengan PHP 8.2.34 dan PHPUnit 11.5.56.

- Unit test: **16 test, 31 assertion, lulus**.
- MySQL integration test pada `db-test`: **7 test, 45 assertion, lulus**.
- PHPStan level 5: **lulus, tanpa error**.
- PHP_CodeSniffer: **lulus**.
- PHP syntax lint: enam file PHP yang diubah pada perbaikan penanganan error dan script low-stock lulus.

Pengujian tersebut memverifikasi source dan perilaku service/repository yang dicakup test. Smoke test HTTP khusus untuk response error 403/404/500 terbaru belum dijalankan. Skenario manual dan bukti yang belum tersedia dicatat di `docs/testing/scenarios.md`.

## Verifikasi terdahulu

Pada **2026-10-04**, smoke test HTTP mencatat: halaman login 200, login Admin mengarah dengan redirect 302, halaman produk/form PO/SO/detail produk 200, produk tidak ditemukan 404, dan API availability mengembalikan JSON 401 tanpa sesi serta 200 dengan sesi terautentikasi. Pemeriksaan CLI low-stock dan Docker app/MySQL juga tercatat berhasil saat itu.

Hasil HTTP terdahulu tersebut tidak mencakup perubahan error handling pada 2026-10-05. Screenshot demo belum disertakan. Integration test mencakup seed, pencarian/filter, ringkasan stok multi-gudang, fulfillment SO, penolakan stok kurang dengan rollback, serta penerimaan PO parsial/penuh. Simulasi request konkuren sungguhan belum direkam; brief tidak mewajibkan thread/paralel sungguhan, tetapi skenario controlled untuk defense tetap perlu disiapkan.
