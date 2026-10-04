# ADR-001: Batas Repository

## Konteks
Aturan bisnis perlu diuji tanpa memerlukan koneksi MySQL, dan service tidak seharusnya memiliki PDO sendiri.

## Keputusan
Service menerima repository melalui constructor injection dan bergantung pada interface repository. Implementasi MySQL digunakan aplikasi; fake digunakan unit test.

## Konsekuensi dan status implementasi
`AuthService`, `InventoryService`, dan `DashboardController` menggunakan `InventoryRepositoryInterface`; `OrderService` menggunakan `OrderRepositoryInterface`. Unit test service memakai `FakeInventoryRepository`. Batas ini belum diterapkan ke seluruh aplikasi: beberapa controller menerima repository MySQL atau PDO langsung, dan endpoint ketersediaan di `public/index.php` mengambil PDO dari repository. Kekurangan tersebut dicatat di `docs/quality/tech-debt.md`.
