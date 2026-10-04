# Tech Debt dan Bukti Submission

1. Entity `SalesOrder` memusatkan aturan lifecycle SO, tetapi data domain lain masih banyak direpresentasikan sebagai associative array.
2. Batas repository belum konsisten: `MasterController`, `InventoryController`, `OrderController`, `AdminUserController`, `WorkflowController`, dan endpoint API memiliki dependency konkret ke repository MySQL atau PDO.
3. `docs/planning/class-diagram-initial.md` adalah rekonstruksi setelah implementasi, bukan bukti diagram dibuat sebelum coding seperti diminta DESIGN-01.
4. Row locking dan rollback tercatat di ADR dan diuji melalui skenario integrasi serial. Request bersamaan belum didemonstrasikan; brief tidak mewajibkan simulasi paralel sungguhan, tetapi skenario controlled masih perlu disiapkan untuk defense.
5. Screenshot desktop/mobile dan demo technical defense belum disertakan.
6. Metadata `.git` sekarang tersedia, tetapi status dan riwayat commit belum dapat diverifikasi dari environment ini karena Git menolak akses atas alasan ownership repository. Periksa dari akun pemilik project sebelum push/tag; jangan merekayasa riwayat lama.
7. `composer quality` lulus pada 2026-10-05. Smoke test HTTP untuk error response setelah perubahan terbaru belum dijalankan; hasil HTTP 2026-10-04 adalah bukti source sebelumnya.
8. Refactoring log mencatat tiga smell dan teknik, tetapi contoh before direkonstruksi dan belum dapat diverifikasi terhadap commit lama.
9. Route lama `/purchase-orders` dan `/sales-orders` masih aktif melalui `OrderController` dan memakai daftar sederhana tanpa filter/paginasi workflow. Navigasi utama menggunakan route `/.../workflow`; tinjau apakah route lama perlu dipertahankan atau disatukan sebelum submission.
