# ADR-002: Pencegahan Oversell

## Konteks
Dua goods issue yang berjalan berdekatan dapat membaca jumlah stok yang sama lalu menurunkan stok seolah-olah masing-masing menjadi satu-satunya transaksi. Baris stok untuk kombinasi produk dan gudang juga mungkin belum tersedia.

## Keputusan
Goods issue dan goods receipt memakai transaksi eksplisit. Repository memastikan baris `ProductStock` tersedia dengan kuantitas nol, kemudian membaca baris produk/gudang menggunakan `SELECT ... FOR UPDATE`. Service menghitung ulang stok dari nilai yang terkunci, menulis ProductStock dan StockLedger, mengubah status order bila relevan, lalu commit. Kegagalan melakukan rollback.

## Konsekuensi dan bukti
Goods issue kedua untuk baris yang sama menunggu lock, lalu membaca kuantitas terbaru; jika stok tidak cukup, operasi ditolak dan perubahan stok, ledger, serta status order di-rollback. Integration test membuktikan rollback saat stok kurang, tetapi belum merekam dua request konkuren; demonstrasi terkontrol tersebut masih perlu disiapkan untuk technical defense.
