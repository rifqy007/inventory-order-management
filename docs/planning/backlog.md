# Status Project dan Backlog Submission

Dokumen ini melacak implementasi dan bukti yang masih perlu disiapkan. Fitur yang ada di source belum otomatis berarti demo/evidence brief sudah lengkap.

## Cakupan yang sudah tersedia di source

- Login/logout, role Admin/Sales/Warehouse Staff, manajemen user, dan master data.
- Produk, upload gambar, stok multi-gudang, pencarian/filter/sort/pagination, serta halaman detail.
- PO, partial/full goods receipt, SO, approval, cancellation, goods issue, dan stock ledger.
- Dashboard per role, CSV, API availability, dan CLI low-stock.
- Unit test, MySQL integration test, PHPStan, PHPCS, Docker Compose, ERD, ADR, class diagram, critique, tech-debt register, dan AI usage log.

## Pekerjaan yang masih diperlukan sebelum submission

- Ikuti [checklist submission](../testing/submission-checklist.md) dari salinan final yang bersih dan simpan bukti asli untuk demo, screenshot, smoke test, dan `composer quality`.
- Jelaskan row lock dan rollback dengan skenario terkontrol saat defense. Brief tidak mewajibkan simulasi thread/paralel sungguhan.
- Class diagram awal yang tersedia adalah rekonstruksi setelah coding, bukan bukti diagram dibuat sebelum coding; sampaikan batas bukti ini dengan jujur.
- Refactoring log memiliki tiga entri, tetapi kutipan *before* direkonstruksi. Buat perubahan refactor nyata dan simpan commit bertag `refactor`; jangan membuat riwayat lama secara retrospektif.
- Pada audit 2026-10-05, Git melaporkan branch `main` tanpa commit. Buat riwayat nyata dengan identitas Git pemilik repository setelah memeriksa staged dan unstaged changes.
- Pastikan `ai-usage-log.md` merangkum prompt, output yang dipakai/ditolak, dan bukti verifikasi terbaru.

## Verifikasi kode terbaru

`composer quality` lulus pada 2026-10-05. Rincian hasil dan batas bukti ada di `docs/testing/results.md` serta `docs/quality/static-analysis.md`.
