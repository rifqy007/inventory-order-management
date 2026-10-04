# Rekonstruksi Class Diagram Awal

Diagram ini merekonstruksi rancangan awal: domain records direncanakan sebagai objek sederhana, dan HTTP direncanakan bergantung pada service serta abstraksi repository.

```mermaid
classDiagram
    class AuthController {
        <<controller>>
    }
    class OrderController {
        <<controller>>
    }
    class AuthService {
        <<service>>
    }
    class OrderService {
        <<service>>
    }
    class User {
        <<entity>>
    }
    class Product {
        <<entity>>
    }
    class PurchaseOrder {
        <<entity>>
    }
    class SalesOrder {
        <<entity>>
    }
    class StockLedgerEntry {
        <<entity>>
    }
    class UserRepository {
        <<interface>>
    }
    class OrderRepository {
        <<interface>>
    }
    AuthController --> AuthService
    OrderController --> OrderService
    AuthService --> UserRepository
    OrderService --> OrderRepository
    UserRepository ..> User
    OrderRepository ..> Product
    OrderRepository ..> PurchaseOrder
    OrderRepository ..> SalesOrder
    OrderRepository ..> StockLedgerEntry
```

**Batas bukti:** diagram ini ditulis setelah implementasi dan bukan artefak bertanggal sebelum coding. Diagram ini dapat dipakai untuk membandingkan rancangan dengan as-built, tetapi tidak membuktikan bahwa initial class diagram dibuat sebelum coding seperti diminta DESIGN-01 pada brief.
