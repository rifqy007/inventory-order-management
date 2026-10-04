# Submission Checklist

Gunakan checklist ini saat memverifikasi salinan final project. Centang hanya setelah langkah benar-benar dijalankan dan simpan screenshot atau output di `docs/testing/evidence/` dengan nama berurutan, misalnya `01-clean-start.png` atau `07-api-response.txt`. Jangan memasukkan password, session cookie, token, atau data pribadi ke bukti.

## Pemeriksaan release dari folder bersih

- [ ] Clone/copy commit final ke folder baru yang tidak memiliki `.env`, `vendor/`, atau volume database lama.
- [ ] Ikuti README: siapkan `.env`, jalankan `docker compose up --build -d`, lalu `docker compose exec app composer install --no-interaction`.
- [ ] Pastikan app, MySQL, dan MySQL test sehat dengan `docker compose ps`.
- [ ] Login dengan akun demo Admin, Sales, dan Warehouse Staff; pastikan otorisasi sesuai role.
- [ ] Jalankan alur PO sampai penerimaan barang dan alur SO sampai goods issue; pastikan ledger dan stok berubah sesuai.
- [ ] Jalankan `docker compose exec app composer quality` dan simpan output terakhir.
- [ ] Jalankan `docker compose exec app php scripts/check-low-stock.php` dan simpan output.

## Bukti demo manual

- [ ] Screenshot empat halaman utama pada desktop dan lebar 360px.
- [ ] Screenshot halaman daftar/detail dengan data dan empty state.
- [ ] Simpan bukti pencarian, filter, sorting, pagination, dashboard per role, dan ekspor CSV.
- [ ] Simpan bukti validasi form dan response aman 403, 404, 500, serta endpoint JSON 200/401/404.
- [ ] Catat hasil demo transaksi PO/SO, termasuk penerimaan parsial, penolakan stok kurang, rollback, dan ledger.
- [ ] Catat walkthrough row lock untuk menjelaskan pencegahan oversell. Brief tidak mengharuskan race test paralel sungguhan.

## Artefak desain dan proses

- [ ] Telusuri class diagram as-built ke source dan jelaskan dependency interface serta concrete.
- [ ] Bahas satu ADR, satu temuan audit SRP, tiga entri refactoring log, dan tech debt.
- [ ] Pastikan ada commit nyata bertag `refactor` yang memperbaiki kode lama, lalu catat hash commit di catatan submission.
- [ ] Periksa status Git, branch, remote, `.env`/credential, dan isi yang akan di-push.
- [ ] Buat tag/release final setelah checklist release terpenuhi.

## Batas bukti historis

`docs/planning/class-diagram-initial.md` saat ini merupakan rekonstruksi setelah implementasi, bukan bukti diagram dibuat sebelum coding. Jangan mengubah tanggal atau menyajikannya sebagai artefak historis. Sampaikan keterbatasan tersebut dengan jujur kepada assessor; bukti proses yang hilang tidak dapat dibuat secara retrospektif.

Refactoring log juga menyatakan cuplikan *before* direkonstruksi. Untuk memenuhi bukti commit, lakukan refactor nyata terhadap kode lama dalam perubahan tersendiri dan gunakan riwayat commit yang benar. Jangan membuat commit/tag dengan tanggal atau isi yang seolah berasal dari pengerjaan lampau.
