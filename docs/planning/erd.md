# ERD Database (As-Built)

ERD berikut merangkum tabel, key utama, dan relasi pada schema yang dipasang melalui `database/schema-and-seed.sql` beserta migration.

```mermaid
erDiagram
    USERS {
        int id PK
        string email UK
        string role
        boolean is_active
    }
    WAREHOUSES {
        int id PK
        string name
        string location
        boolean is_active
    }
    CATEGORIES {
        int id PK
        string name UK
        string description
    }
    PRODUCTS {
        int id PK
        string sku UK
        string name
        int category_id FK
        string unit
        decimal purchase_price
        decimal selling_price
        int reorder_point
        string image_path
        boolean is_active
    }
    PRODUCT_STOCKS {
        int product_id PK, FK
        int warehouse_id PK, FK
        int quantity
        timestamp updated_at
    }
    SUPPLIERS {
        int id PK
        string name
        string contact
        string address
        boolean is_active
    }
    CUSTOMERS {
        int id PK
        string name
        string contact
        string address
        boolean is_active
    }
    PURCHASE_ORDERS {
        int id PK
        string order_number UK
        int supplier_id FK
        int warehouse_id FK
        int created_by FK
        string status
        date order_date
    }
    PURCHASE_ORDER_ITEMS {
        int id PK
        int purchase_order_id FK
        int product_id FK
        int quantity
        int received_quantity
        decimal purchase_price
    }
    SALES_ORDERS {
        int id PK
        string order_number UK
        int customer_id FK
        int warehouse_id FK
        int created_by FK
        int approved_by FK
        string status
        date order_date
    }
    SALES_ORDER_ITEMS {
        int id PK
        int sales_order_id FK
        int product_id FK
        int quantity
        decimal selling_price
    }
    STOCK_LEDGER {
        bigint id PK
        int product_id FK
        int warehouse_id FK
        int performed_by FK
        string movement_type
        int quantity
        string reference_type
        int reference_id
        timestamp created_at
    }

    USERS ||--o{ PURCHASE_ORDERS : creates
    USERS ||--o{ SALES_ORDERS : creates
    USERS o|--o{ SALES_ORDERS : approves
    USERS ||--o{ STOCK_LEDGER : performs
    CATEGORIES ||--o{ PRODUCTS : classifies
    PRODUCTS ||--o{ PRODUCT_STOCKS : stocked_at
    WAREHOUSES ||--o{ PRODUCT_STOCKS : contains
    WAREHOUSES ||--o{ PURCHASE_ORDERS : destination
    WAREHOUSES ||--o{ SALES_ORDERS : source
    SUPPLIERS ||--o{ PURCHASE_ORDERS : supplies
    CUSTOMERS ||--o{ SALES_ORDERS : orders
    PURCHASE_ORDERS ||--o{ PURCHASE_ORDER_ITEMS : includes
    SALES_ORDERS ||--o{ SALES_ORDER_ITEMS : includes
    PRODUCTS ||--o{ PURCHASE_ORDER_ITEMS : purchased
    PRODUCTS ||--o{ SALES_ORDER_ITEMS : sold
    PRODUCTS ||--o{ STOCK_LEDGER : movement
    WAREHOUSES ||--o{ STOCK_LEDGER : movement_at
```

`PRODUCT_STOCKS` memakai composite primary key `(product_id, warehouse_id)`. `STOCK_LEDGER.reference_type` dan `reference_id` adalah pasangan referensi polimorfik untuk PO, SO, atau penyesuaian; keduanya tidak membentuk foreign key langsung ke satu tabel transaksi. Tipe kolom lengkap, enum, index, dan check constraint tetap mengikuti schema SQL.

Contoh index: `idx_sales_orders_status_date (status, order_date)` mendukung filter status dan pengurutan tanggal pada daftar/dashboard order. Contoh transaksi multi-tabel dijelaskan di `docs/architecture/adr-002-stock-locking.md`: pembaruan ProductStock, penulisan StockLedger, dan perubahan status SO di-commit atau di-rollback bersama.
