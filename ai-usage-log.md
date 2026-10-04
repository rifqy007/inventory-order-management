# AI Usage Log

- Tool: Microsoft 365 Copilot
- Tujuan: Membantu membuat baseline project pembelajaran, struktur layer, komentar, Docker, dan schema.
- Review: Pengguna wajib membaca setiap class dan menguji seluruh behavior.
- Verification: Jalankan PHP lint, PHPUnit, PHPStan, alur login, stok, PO/SO, dan cek database.
- Catatan: Tidak ada credential produksi atau data client yang digunakan.

## OpenAI Codex

- Tujuan: Meninjau, memperbaiki, dan menyiapkan aplikasi Inventory & Order Management terhadap Project Brief.
- Ringkasan prompt yang disanitasi: Audit project dan bandingkan dengan brief; perbaiki alur inventori/PO/SO serta konfigurasi; rapikan login/form/dashboard responsif per role; tambahkan pengurutan tabel dan pagination; siapkan struktur file untuk Git.
- Output yang digunakan: Perubahan source PHP, repository/service/entity, database migration, Docker/config, tests dan laporan; perubahan CSS/JavaScript/template untuk form, dashboard, daftar produk dan order; serta `.gitignore`, README, backlog, tech-debt, dan penghapusan cache PHPUnit serta README duplikat.
- Output yang ditolak/tidak digunakan: Framework, ORM, library UI eksternal, gambar/asset pihak ketiga, atau credential/data eksternal tidak ditambahkan.
- Review: Perubahan ditinjau terhadap struktur route/repository, hak akses tiga role, isi Project Brief, dan file yang dipakai Docker. Source, migration/seed, tests, dokumentasi bukti, `.env.example`, serta file konfigurasi tetap disimpan.
- Verifikasi sebelumnya: Hasil PHPUnit/PHPStan/PHPCS dan smoke test HTTP terdahulu tercatat di `docs/testing/results.md` dan `docs/quality/static-analysis.md`.
- Verifikasi 2026-10-05: `composer quality` berhasil (unit: 16 tests/31 assertions; integration: 7 tests/45 assertions; PHPStan dan PHPCS lulus). PHP lint berhasil pada enam file PHP yang diubah. Smoke test HTTP khusus untuk perubahan error belum dijalankan.
- Output tambahan yang digunakan: penanganan bootstrap failure dan exception aplikasi dengan response HTML/JSON aman, halaman error 403/404/500 yang tautannya berfungsi, status 403 untuk token CSRF tidak valid, serta output dan exit code yang jelas pada script low-stock.
- Ringkasan prompt tersanitasi berikutnya: periksa seluruh dokumen project terhadap Project Brief, sesuaikan diagram class/ERD, dan selaraskan backlog serta catatan testing dengan bukti terbaru.
- Output dokumentasi yang digunakan: ERD dengan key dan relasi schema, class diagram as-built yang mencatat dependency konkret/interface, ADR dengan status implementasi, skenario demo per requirement, serta daftar batas bukti dan pekerjaan submission.
- Verifikasi dokumentasi: isi dokumen dibandingkan dengan schema SQL, dependency/controller yang ada di source, hasil `composer quality` 2026-10-05, dan hasil smoke test HTTP historis yang tetap diberi tanggal.
- Catatan: Tidak ada credential produksi, data client, atau PII yang digunakan dalam prompt.

## OpenAI Codex — audit kesiapan submission

- Tujuan: Menindaklanjuti gap terhadap Project Brief dan menyiapkan checklist bukti yang belum tersedia.
- Ringkasan prompt yang disanitasi: lengkapi bagian project yang belum ada agar siap memenuhi brief.
- Output yang digunakan: `docs/testing/submission-checklist.md`, serta tautannya dari README dan backlog; checklist memisahkan langkah release bersih, bukti demo, dan artefak desain/proses.
- Output yang ditolak/tidak digunakan: tidak membuat screenshot, output test, class diagram pra-coding, atau riwayat commit/tag retrospektif karena itu akan menyatakan bukti yang belum terjadi sebagai fakta.
- Verifikasi: dibandingkan dengan bagian Docker/testing, DESIGN-01/03, Git, dan checklist submission di Project Brief. `docker compose exec app composer quality` lulus pada 2026-10-05: 16 unit test/31 assertion, 7 integration test/45 assertion, PHPStan tanpa error, dan PHPCS sukses. Git pada audit membaca branch `main` tanpa commit; detail tindak lanjut dicatat di backlog.
