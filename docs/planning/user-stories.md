# User Stories

## Admin

- Sebagai Admin, saya ingin mengelola user, role, dan status akun agar akses aplikasi dapat dikendalikan.
- Sebagai Admin, saya ingin mengelola produk, kategori, gudang, supplier, dan customer agar data transaksi konsisten.
- Sebagai Admin, saya ingin mencari dan memeriksa stok total serta stok per gudang agar reorder point dapat dipantau.
- Sebagai Admin, saya ingin menyetujui atau menolak Sales Order yang diajukan Sales lain agar segregation of duties terjaga.
- Sebagai Admin, saya ingin melihat dashboard dan mengunduh laporan agar dapat memantau operasional.

## Sales

- Sebagai Sales, saya ingin melihat katalog dan stok yang tersedia agar dapat menyiapkan pesanan.
- Sebagai Sales, saya ingin membuat dan mengajukan Sales Order milik saya agar pesanan dapat ditinjau Admin.
- Sebagai Sales, saya ingin memantau status dan membatalkan order saya yang masih memenuhi aturan agar proses penjualan tetap terkendali.

## Warehouse Staff

- Sebagai Warehouse Staff, saya ingin melihat stok per gudang dan daftar PO/SO yang perlu diproses agar pekerjaan gudang dapat diprioritaskan.
- Sebagai Warehouse Staff, saya ingin membuat Purchase Order dan mencatat penerimaan barang sebagian atau penuh agar stok bertambah dengan riwayat.
- Sebagai Warehouse Staff, saya ingin memproses goods issue untuk Sales Order yang disetujui agar stok berkurang secara transaksional.
- Sebagai Warehouse Staff, saya ingin melihat dashboard dan stock ledger agar pergerakan stok dapat ditelusuri.

## Aturan bersama

- User yang belum login tidak dapat membuka halaman terlindungi.
- Perubahan stok harus melalui service dan menghasilkan stock ledger dalam transaksi yang sama.
- Sales tidak dapat menyetujui Sales Order, dan Admin tidak dapat menyetujui order yang dibuatnya sendiri.
- Produk, supplier, customer, gudang, dan akun dinonaktifkan bila perlu dipertahankan untuk riwayat transaksi.
