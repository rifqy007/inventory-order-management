# Skenario Pengujian dan Bukti Project Brief

Dokumen ini memisahkan pengujian otomatis yang sudah tercatat dari demo manual yang masih perlu direkam. Lulusnya unit/integration test tidak otomatis membuktikan seluruh alur UI atau bukti screenshot.

## Verifikasi otomatis

Pemeriksaan terakhir yang tercatat dijalankan pada 2026-10-05 dengan `composer quality` di container:

- Unit: autentikasi service, status user, aturan stok, rollback fake repository, lifecycle dan validasi order, serta header sorting tabel.
- Integration pada MySQL `db-test`: volume seed, pencarian dan ringkasan stok produk, daftar order/filter/sort, fulfillment SO dan ledger, penolakan saat stok kurang dengan rollback, serta penerimaan PO parsial/penuh.
- PHPStan level 5 dan PHP_CodeSniffer: lulus. Rincian ada di `docs/testing/results.md` dan `docs/quality/static-analysis.md`.

Integration test menggunakan database test terpisah. Pengujian ini bukan simulasi dua request konkuren secara bersamaan.

## Demo manual yang harus dilengkapi

Rekam hasil, screenshot, atau output yang sesuai untuk setiap skenario berikut sebelum submission.

| Requirement | Skenario | Hasil yang diharapkan | Bukti saat ini |
|---|---|---|---|
| AUTH-01 | Login Admin, Sales, dan Warehouse Staff; password salah; akun nonaktif; akses halaman tanpa sesi; periksa session ID setelah login | Login sukses sesuai role; kegagalan memberi pesan aman; halaman terlindungi meminta login | Belum direkam lengkap |
| AUTH-02 | Logout lalu buka kembali URL terlindungi | Session autentikasi berakhir dan pengguna kembali diminta login | Belum direkam |
| USR-01 | Admin membuat, melihat, mengubah, menonaktifkan user; coba email duplikat; coba akses sebagai Sales dan Warehouse Staff | CRUD dan email unik berfungsi; role non-admin mendapat 403 | Belum direkam |
| PRD-01 | Buat/edit/nonaktifkan produk; uji SKU duplikat, harga/reorder negatif, gambar valid dan file tidak valid | Validasi menolak data salah; produk yang telah dipakai dipertahankan melalui status nonaktif; gambar divalidasi | Belum direkam lengkap |
| WH-01 | Tampilkan satu produk dengan stok berbeda pada dua gudang | Total dan jumlah tiap gudang terlihat benar | Integration test memeriksa dua gudang; screenshot belum direkam |
| PO-01 | Buat PO, ubah ke Ordered, terima barang sebagian lalu penuh | Status PartiallyReceived/Received, ProductStock, dan Receipt ledger konsisten | Integration test tercatat; demo UI belum direkam |
| SO-01 | Sales membuat/mengajukan SO; coba approve sebagai Sales; Admin menyetujui/menolak; Warehouse memproses; coba issue saat stok kurang | Authorization dipatuhi; status, stok, dan Issue ledger konsisten; stok kurang menyebabkan rollback | Integration test lifecycle dan stok kurang tercatat; demo role belum direkam |
| VIEW-01 | Lihat daftar/detail produk, PO, SO saat ada data dan saat kosong sebagai role yang sesuai | Detail sesuai hak akses; empty state menjelaskan kondisi | Belum direkam lengkap |
| FIND-01 | Gabungkan pencarian, filter, sort tanggal, dan pagination; lanjut ke halaman kedua | 10 baris per halaman; filter tetap aktif; seed minimal 30 produk dan 25 order gabungan | Cakupan query seed/search ada di integration test; kombinasi UI belum direkam |
| DASH-01 | Tampilkan dashboard sebagai Admin, Sales, dan Warehouse Staff | Metrik sesuai role berasal dari query data | Integration test menjalankan query dashboard; screenshot/demo belum direkam |
| REPORT-01 | Ekspor ledger dan status order untuk rentang tanggal berbeda | CSV berisi hanya data sesuai rentang | Query laporan ada di integration test; file demo belum direkam |
| API-01 | Panggil availability dengan sesi dan tanpa sesi; gunakan SKU ada dan tidak ada | 200/401/404 dengan `application/json` | Smoke test lama tercatat pada hasil 2026-10-04; belum diulang setelah perubahan error terbaru |
| VAL-01 / ERR-01 | Kirim field wajib kosong, enum/status/tanggal/foreign key/angka salah; akses role terlarang, URL/data hilang, dan failure path 500 yang aman | Data invalid tidak disimpan; input relevan dipertahankan; 403/404/500 aman tanpa stack trace | Unit/integration mencakup sebagian validasi; demo manual belum direkam |
| UI-01 | Periksa login, dashboard, daftar, detail, dan form pada lebar 360px dan desktop | Navigasi, tabel, label, focus state, dan kontras dapat digunakan | Screenshot belum disertakan |
| DB-01 | Buat database dari kosong; periksa FK, constraint, index, prepared statement, serta transaksi receipt/issue | Schema/seed membangun data; operasi multi-tabel atomik | Integration test memakai MySQL; penjelasan index ada di ERD dan transaksi ada di ADR-002; bukti demo build-from-empty belum direkam |
| JOB-01 | Jalankan `docker compose exec app php scripts/check-low-stock.php` | Script menampilkan ringkasan stok di bawah reorder point | Run terdahulu tercatat pada `docs/testing/results.md`; ulangi pada source final |
| ARCH-01/02 | Tunjukkan unit test dengan fake repository; jelaskan lock pada stock row dan skenario stok habis setelah transaksi pertama | Service dapat diuji tanpa MySQL; oversell dicegah oleh transaksi dan row lock | Unit/integration test fake dan rollback tercatat; demo lock konkuren masih perlu disiapkan |
| DESIGN-01/02/03 | Telusuri initial/as-built diagram, ADR, refactoring log, critique SRP, dan tech debt saat defense | Diagram dan keputusan sesuai implementasi; keterbatasan rekonstruksi dijelaskan | Dokumen tersedia; initial diagram bukan bukti pra-coding dan before/after log direkonstruksi |

## Cara menjalankan pemeriksaan otomatis

```powershell
docker compose exec app composer quality
docker compose exec app php scripts/check-low-stock.php
```
