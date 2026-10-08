# Hasil Static Analysis

## SonarQube

Scan **8 Oktober 2026** berhasil mengunggah analisis ke SonarQube. PHPUnit lulus dengan **24 test dan 83 assertion**. Quality Gate masih **gagal** karena coverage New Code **21,8%** dengan ambang **80%**. Tambahkan test untuk kode baru yang belum tercakup, lalu jalankan scan kembali. Log juga mencatat peringatan SCM karena SonarQube tidak memperoleh blame information untuk sejumlah file; peringatan ini tidak mengubah hasil coverage.

## Verifikasi terbaru

Tanggal **2026-10-05**, dijalankan di container aplikasi sebagai bagian dari `composer quality`:

- PHPStan level 5 (`composer analyse`): **lulus, tidak ada error** pada 28 file.
- PHP_CodeSniffer (`composer format-check`): **lulus**.
- Pemeriksaan ulang setelah perubahan feedback login akun nonaktif: unit **17 test/32 assertion**, integration **7 test/45 assertion**, PHPStan dan PHPCS lulus.
- PHP syntax lint: enam file PHP yang diubah pada perbaikan error handling dan script low-stock: **lulus**.

Pemeriksaan syntax untuk seluruh source sebelumnya mencatat 50 file lulus pada 2026-10-04. Jalankan kembali lint seluruh source setelah perubahan berikutnya.

PHPCS adalah pemeriksaan format, bukan bukti perilaku runtime. Test otomatis dan batas cakupannya ada di `docs/testing/results.md`. Smoke test HTTP untuk response error terbaru belum dijalankan. Pengaturan PHPCS menonaktifkan advisory panjang baris; query SQL/baris view yang panjang masih dapat dirapikan.
