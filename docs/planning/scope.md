# Scope Project

Dokumen ini merangkum scope dari *Project Brief - Programmer*, bukan mengganti requirement lengkap pada brief.

## Termasuk dalam scope

- Aplikasi web Inventory & Order Management dengan PHP Native OOP, HTML/CSS/Vanilla JavaScript, MySQL 8, dan Docker Compose.
- Tiga role: Admin, Sales, dan Warehouse Staff, dengan otorisasi server-side dan segregation of duties.
- Login/logout, manajemen user, master data, produk, upload gambar opsional, dan stok multi-gudang.
- Purchase Order, penerimaan sebagian/penuh, Sales Order, approval/rejection, pengeluaran stok, transaksi aman, dan stock ledger.
- Dashboard per role, pencarian/filter/sort/pagination, laporan CSV, dan minimal satu endpoint JSON.
- Unit/integration test, static analysis, class diagram, ADR, refactor log, critique, tech-debt register, dan bukti testing.

## Di luar scope wajib

- Framework backend/frontend, ORM, dan DI container framework.
- Microservices, message queue, cloud deployment, CI/CD, Kubernetes, notifikasi real-time, aplikasi mobile, dan scheduler otomatis.
- Race test request paralel sungguhan; mekanisme stok tetap harus dijelaskan dan dibuktikan melalui skenario terkontrol.

Project memiliki workflow SonarQube GitHub Actions sebagai alat kualitas opsional. CI/CD tidak menjadi syarat fitur wajib dan workflow tersebut hanya berjalan pada repository GitHub.

Fitur bonus hanya dikerjakan setelah seluruh requirement wajib berfungsi. Bila terjadi perbedaan antara ringkasan ini dan brief, gunakan brief sebagai acuan utama.
