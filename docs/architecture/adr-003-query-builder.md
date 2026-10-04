# ADR-003: Query Builder Internal

## Konteks
Daftar inventori memerlukan query dengan proyeksi kolom eksplisit, filter, urutan, dan paginasi. Nilai request tidak boleh digabung langsung ke SQL.

## Keputusan
Repository inventori menggunakan `QueryBuilder` internal untuk query baca. Query tidak menerima wildcard `SELECT *`; nilai di-bind, sedangkan nama kolom sort, arah sort, dan ukuran halaman dibatasi allowlist. Mutasi memakai prepared statement; operasi transaksi stok tetap dikelola melalui service dan repository.

## Konsekuensi
Query baca umum dapat dirangkai tanpa menaruh SQL langsung di controller, sementara input sort dan nilai filter tetap dibatasi. Query workflow khusus masih menggunakan SQL prepared statement di implementasi repository.
