# Critique Exercise

## Contoh

`public/index.php` melakukan route matching, query PDO availability, authorization, dan menulis JSON response.

## Smell dan prinsip

- Multiple responsibilities / long method: front controller mencampur routing dengan data access dan response serialization.
- Single Responsibility Principle: perubahan kontrak API atau query ketersediaan memaksa perubahan file routing yang sama.
- Dependency Inversion: route bergantung pada PDO melalui `MysqlInventoryRepository::pdo()`.

## Arah refactor

Buat `AvailabilityController` yang menerima `AvailabilityService` melalui constructor. Service meminta kontrak repository; repository mengembalikan stok per gudang. Controller mengatur status HTTP dan JSON. Route hanya memetakan method/path ke controller. Pertahankan pemeriksaan session server-side dan test 200/401/404.

## Status saat ini

Pada 2026-10-05 penanganan exception dan response generik untuk HTML/JSON dirapikan, tetapi query availability masih ditangani di `public/index.php` melalui PDO dari repository. Smell SRP dan Dependency Inversion pada endpoint tersebut belum dihilangkan; ini adalah usulan refactor, bukan perubahan yang sudah selesai.
