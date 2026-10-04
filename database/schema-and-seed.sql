/* ============================================================
   DATABASE INVENTORY ORDER MANAGEMENT
   ============================================================
   Keterangan:
   - Database menggunakan MySQL 8 atau MariaDB yang mendukung CHECK.
   - Urutan pembuatan tabel sudah disesuaikan dengan foreign key.
   - Password akun demo: Password123!
   ============================================================ */


/* ============================================================
   1. TABEL USERS
   ============================================================
   Menyimpan akun pengguna aplikasi.

   Role:
   - Admin          : Mengelola master data dan approval.
   - Sales          : Membuat Sales Order.
   - WarehouseStaff : Memproses penerimaan dan pengeluaran barang.
   ============================================================ */

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    role ENUM(
        'Admin',
        'Sales',
        'WarehouseStaff'
    ) NOT NULL,

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


/* ============================================================
   2. TABEL WAREHOUSES
   ============================================================
   Menyimpan data gudang.
   ============================================================ */

CREATE TABLE warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,
    location VARCHAR(255),

    is_active BOOLEAN NOT NULL DEFAULT TRUE
);


/* ============================================================
   3. TABEL CATEGORIES
   ============================================================
   Menyimpan kategori produk.
   ============================================================ */

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);


/* ============================================================
   4. TABEL PRODUCTS
   ============================================================
   Menyimpan data utama produk.
   ============================================================ */

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,

    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,

    category_id INT NOT NULL,

    unit VARCHAR(20) NOT NULL,

    purchase_price DECIMAL(15, 2) NOT NULL,
    selling_price DECIMAL(15, 2) NOT NULL,

    reorder_point INT NOT NULL DEFAULT 0,

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT chk_products_purchase_price
        CHECK (purchase_price >= 0),

    CONSTRAINT chk_products_selling_price
        CHECK (selling_price >= 0),

    CONSTRAINT chk_products_reorder_point
        CHECK (reorder_point >= 0),

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories (id),

    INDEX idx_products_name (name)
);


/* ============================================================
   5. TABEL PRODUCT STOCKS
   ============================================================
   Menyimpan stok terakhir setiap produk pada setiap gudang.

   Primary key merupakan gabungan:
   - product_id
   - warehouse_id

   Artinya, satu produk hanya memiliki satu baris stok
   untuk setiap gudang.
   ============================================================ */

CREATE TABLE product_stocks (
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 0,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (product_id, warehouse_id),

    CONSTRAINT chk_product_stocks_quantity
        CHECK (quantity >= 0),

    CONSTRAINT fk_product_stocks_product
        FOREIGN KEY (product_id)
        REFERENCES products (id),

    CONSTRAINT fk_product_stocks_warehouse
        FOREIGN KEY (warehouse_id)
        REFERENCES warehouses (id)
);


/* ============================================================
   6. TABEL SUPPLIERS
   ============================================================
   Menyimpan data pemasok barang.
   ============================================================ */

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,
    contact VARCHAR(100),
    address TEXT,

    is_active BOOLEAN NOT NULL DEFAULT TRUE
);


/* ============================================================
   7. TABEL CUSTOMERS
   ============================================================
   Menyimpan data pelanggan.
   ============================================================ */

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,
    contact VARCHAR(100),
    address TEXT,

    is_active BOOLEAN NOT NULL DEFAULT TRUE
);


/* ============================================================
   8. TABEL PURCHASE ORDERS
   ============================================================
   Menyimpan bagian header Purchase Order.

   Status:
   - Draft             : PO masih berupa rancangan.
   - Ordered           : PO sudah dipesan ke supplier.
   - PartiallyReceived : Barang diterima sebagian.
   - Received          : Semua barang sudah diterima.
   - Cancelled         : PO dibatalkan.
   ============================================================ */

CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,

    order_number VARCHAR(30) NOT NULL UNIQUE,

    supplier_id INT NOT NULL,
    warehouse_id INT NOT NULL,

    status ENUM(
        'Draft',
        'Ordered',
        'PartiallyReceived',
        'Received',
        'Cancelled'
    ) NOT NULL DEFAULT 'Draft',

    order_date DATE NOT NULL,

    created_by INT NOT NULL,

    CONSTRAINT fk_purchase_orders_supplier
        FOREIGN KEY (supplier_id)
        REFERENCES suppliers (id),

    CONSTRAINT fk_purchase_orders_warehouse
        FOREIGN KEY (warehouse_id)
        REFERENCES warehouses (id),

    CONSTRAINT fk_purchase_orders_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (id),

    INDEX idx_purchase_orders_status_date (
        status,
        order_date
    )
);


/* ============================================================
   9. TABEL PURCHASE ORDER ITEMS
   ============================================================
   Menyimpan detail produk yang terdapat dalam Purchase Order.

   received_quantity digunakan untuk mencatat jumlah barang
   yang sudah diterima dari supplier.
   ============================================================ */

CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    purchase_order_id INT NOT NULL,
    product_id INT NOT NULL,

    quantity INT NOT NULL,
    received_quantity INT NOT NULL DEFAULT 0,

    purchase_price DECIMAL(15, 2) NOT NULL,

    CONSTRAINT chk_purchase_order_items_quantity
        CHECK (quantity > 0),

    CONSTRAINT chk_purchase_order_items_received_quantity
        CHECK (received_quantity >= 0 AND received_quantity <= quantity),

    CONSTRAINT chk_purchase_order_items_purchase_price
        CHECK (purchase_price >= 0),

    CONSTRAINT fk_purchase_order_items_order
        FOREIGN KEY (purchase_order_id)
        REFERENCES purchase_orders (id),

    CONSTRAINT fk_purchase_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products (id),

    CONSTRAINT uq_purchase_order_items_product
        UNIQUE (purchase_order_id, product_id)
);


/* ============================================================
   10. TABEL SALES ORDERS
   ============================================================
   Menyimpan bagian header Sales Order.

   Status:
   - Draft           : SO masih berupa rancangan.
   - PendingApproval : Menunggu persetujuan Admin.
   - Approved        : Sudah disetujui Admin.
   - Fulfilled       : Barang sudah dikeluarkan dari gudang.
   - Cancelled       : SO dibatalkan.
   ============================================================ */

CREATE TABLE sales_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,

    order_number VARCHAR(30) NOT NULL UNIQUE,

    customer_id INT NOT NULL,
    warehouse_id INT NOT NULL,

    status ENUM(
        'Draft',
        'PendingApproval',
        'Approved',
        'Fulfilled',
        'Cancelled'
    ) NOT NULL DEFAULT 'Draft',

    order_date DATE NOT NULL,

    created_by INT NOT NULL,
    approved_by INT NULL,

    CONSTRAINT fk_sales_orders_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers (id),

    CONSTRAINT fk_sales_orders_warehouse
        FOREIGN KEY (warehouse_id)
        REFERENCES warehouses (id),

    CONSTRAINT fk_sales_orders_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (id),

    CONSTRAINT fk_sales_orders_approved_by
        FOREIGN KEY (approved_by)
        REFERENCES users (id),

    INDEX idx_sales_orders_status_date (
        status,
        order_date
    )
);


/* ============================================================
   11. TABEL SALES ORDER ITEMS
   ============================================================
   Menyimpan detail produk dalam Sales Order.
   ============================================================ */

CREATE TABLE sales_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    sales_order_id INT NOT NULL,
    product_id INT NOT NULL,

    quantity INT NOT NULL,
    selling_price DECIMAL(15, 2) NOT NULL,

    CONSTRAINT chk_sales_order_items_quantity
        CHECK (quantity > 0),

    CONSTRAINT chk_sales_order_items_selling_price
        CHECK (selling_price >= 0),

    CONSTRAINT fk_sales_order_items_order
        FOREIGN KEY (sales_order_id)
        REFERENCES sales_orders (id),

    CONSTRAINT fk_sales_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products (id),

    CONSTRAINT uq_sales_order_items_product
        UNIQUE (sales_order_id, product_id)
);


/* ============================================================
   12. TABEL STOCK LEDGER
   ============================================================
   Menyimpan seluruh riwayat pergerakan stok.

   Movement type:
   - Receipt    : Barang masuk.
   - Issue      : Barang keluar.
   - Adjustment : Penyesuaian stok.

   Contoh reference_type:
   - PURCHASE_ORDER
   - SALES_ORDER
   - STOCK_ADJUSTMENT
   ============================================================ */

CREATE TABLE stock_ledger (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,

    movement_type ENUM(
        'Receipt',
        'Issue',
        'Adjustment'
    ) NOT NULL,

    quantity INT NOT NULL,

    reference_type VARCHAR(30) NOT NULL,
    reference_id INT NOT NULL,

    performed_by INT NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_stock_ledger_quantity
        CHECK (quantity > 0),

    CONSTRAINT fk_stock_ledger_product
        FOREIGN KEY (product_id)
        REFERENCES products (id),

    CONSTRAINT fk_stock_ledger_warehouse
        FOREIGN KEY (warehouse_id)
        REFERENCES warehouses (id),

    CONSTRAINT fk_stock_ledger_performed_by
        FOREIGN KEY (performed_by)
        REFERENCES users (id),

    INDEX idx_stock_ledger_created_at (created_at),

    INDEX idx_stock_ledger_product_warehouse (
        product_id,
        warehouse_id
    ),

    INDEX idx_stock_ledger_reference (
        reference_type,
        reference_id
    )
);


/* ============================================================
   DATA DEMO
   ============================================================ */


/* ============================================================
   13. DATA DEMO USERS
   ============================================================
   Password seluruh akun demo: Password123!
   Hash disimpan menggunakan algoritma bcrypt.
   ============================================================ */

INSERT INTO users (
    name,
    email,
    password_hash,
    role
)
VALUES
    (
        'Admin Demo',
        'admin@example.com',
        '$2y$10$WqHKXtBfZ4RO8hczqKpOd.2euUA7DloSQulHUGM3gAydcKloZdaKK',
        'Admin'
    ),
    (
        'Sales One',
        'sales1@example.com',
        '$2y$10$WqHKXtBfZ4RO8hczqKpOd.2euUA7DloSQulHUGM3gAydcKloZdaKK',
        'Sales'
    ),
    (
        'Sales Two',
        'sales2@example.com',
        '$2y$10$WqHKXtBfZ4RO8hczqKpOd.2euUA7DloSQulHUGM3gAydcKloZdaKK',
        'Sales'
    ),
    (
        'Warehouse One',
        'warehouse1@example.com',
        '$2y$10$WqHKXtBfZ4RO8hczqKpOd.2euUA7DloSQulHUGM3gAydcKloZdaKK',
        'WarehouseStaff'
    ),
    (
        'Warehouse Two',
        'warehouse2@example.com',
        '$2y$10$WqHKXtBfZ4RO8hczqKpOd.2euUA7DloSQulHUGM3gAydcKloZdaKK',
        'WarehouseStaff'
    );


/* ============================================================
   14. DATA DEMO WAREHOUSES
   ============================================================ */

INSERT INTO warehouses (
    name,
    location
)
VALUES
    ('Gudang Jakarta', 'Jakarta'),
    ('Gudang Bandung', 'Bandung');


/* ============================================================
   15. DATA DEMO CATEGORIES
   ============================================================ */

INSERT INTO categories (
    name,
    description
)
VALUES
    ('Elektronik', 'Perangkat elektronik'),
    ('ATK', 'Alat tulis kantor'),
    ('Aksesori', 'Aksesori umum');


/* ============================================================
   16. DATA DEMO SUPPLIERS
   ============================================================ */

INSERT INTO suppliers (
    name,
    contact,
    address
)
VALUES
    (
        'Supplier Demo',
        '021-555',
        'Jakarta'
    );


/* ============================================================
   17. DATA DEMO CUSTOMERS
   ============================================================ */

INSERT INTO customers (
    name,
    contact,
    address
)
VALUES
    (
        'Customer Demo',
        '0812',
        'Jakarta'
    );


/* ============================================================
   18. DATA DEMO PRODUCTS
   ============================================================
   Membuat 30 produk secara otomatis.

   Contoh:
   - SKU-001
   - SKU-002
   - SKU-003
   - dan seterusnya sampai SKU-030.

   Rumus kategori:
   ((n - 1) % 3) + 1

   Rumus tersebut membagi produk secara bergantian ke dalam
   kategori dengan ID 1, 2, dan 3.
   ============================================================ */

INSERT INTO products (
    sku,
    name,
    category_id,
    unit,
    purchase_price,
    selling_price,
    reorder_point
)
SELECT
    CONCAT('SKU-', LPAD(n, 3, '0')) AS sku,

    CONCAT('Produk Demo ', n) AS name,

    ((n - 1) % 3) + 1 AS category_id,

    'pcs' AS unit,

    10000 + (n * 100) AS purchase_price,

    15000 + (n * 150) AS selling_price,

    5 + (n % 4) AS reorder_point

FROM (
    SELECT 1 AS n
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
    UNION ALL SELECT 8
    UNION ALL SELECT 9
    UNION ALL SELECT 10
    UNION ALL SELECT 11
    UNION ALL SELECT 12
    UNION ALL SELECT 13
    UNION ALL SELECT 14
    UNION ALL SELECT 15
    UNION ALL SELECT 16
    UNION ALL SELECT 17
    UNION ALL SELECT 18
    UNION ALL SELECT 19
    UNION ALL SELECT 20
    UNION ALL SELECT 21
    UNION ALL SELECT 22
    UNION ALL SELECT 23
    UNION ALL SELECT 24
    UNION ALL SELECT 25
    UNION ALL SELECT 26
    UNION ALL SELECT 27
    UNION ALL SELECT 28
    UNION ALL SELECT 29
    UNION ALL SELECT 30
) AS product_numbers;


/* ============================================================
   19. DATA DEMO PRODUCT STOCKS
   ============================================================
   Memasukkan stok awal setiap produk ke dua gudang.
   ============================================================ */

/* Stok awal untuk Gudang Jakarta dengan warehouse_id = 1. */
INSERT INTO product_stocks (
    product_id,
    warehouse_id,
    quantity
)
SELECT
    id AS product_id,
    1 AS warehouse_id,
    id % 12 AS quantity
FROM products;


/* Stok awal untuk Gudang Bandung dengan warehouse_id = 2. */
INSERT INTO product_stocks (
    product_id,
    warehouse_id,
    quantity
)
SELECT
    id AS product_id,
    2 AS warehouse_id,
    id % 9 AS quantity
FROM products;


/* ============================================================
   20. DATA DEMO PURCHASE ORDERS
   ============================================================ */

INSERT INTO purchase_orders (
    order_number,
    supplier_id,
    warehouse_id,
    status,
    order_date,
    created_by
)
VALUES
    (
        'PO-001',
        1,
        1,
        'Ordered',
        CURRENT_DATE,
        1
    ),
    (
        'PO-002',
        1,
        2,
        'PartiallyReceived',
        CURRENT_DATE,
        1
    );


/* ============================================================
   21. DATA DEMO SALES ORDERS
   ============================================================ */

INSERT INTO sales_orders (
    order_number,
    customer_id,
    warehouse_id,
    status,
    order_date,
    created_by
)
VALUES
    (
        'SO-001',
        1,
        1,
        'PendingApproval',
        CURRENT_DATE,
        2
    ),
    (
        'SO-002',
        1,
        2,
        'Approved',
        CURRENT_DATE,
        3
    ),
    (
        'SO-003',
        1,
        1,
        'Cancelled',
        CURRENT_DATE,
        2
    );

/* Tambahan data demo agar pencarian, filter, dan paging order bisa diperagakan. */
INSERT INTO purchase_orders (
    order_number, supplier_id, warehouse_id, status, order_date, created_by
)
WITH RECURSIVE demo_numbers AS (
    SELECT 3 AS n
    UNION ALL SELECT n + 1 FROM demo_numbers WHERE n < 12
)
SELECT
    CONCAT('PO-', LPAD(n, 3, '0')),
    1,
    MOD(n, 2) + 1,
    CASE MOD(n, 4)
        WHEN 0 THEN 'PartiallyReceived'
        WHEN 1 THEN 'Received'
        WHEN 2 THEN 'Cancelled'
        ELSE 'Ordered'
    END,
    DATE_SUB(CURRENT_DATE, INTERVAL n DAY),
    1
FROM demo_numbers;

INSERT INTO sales_orders (
    order_number, customer_id, warehouse_id, status, order_date, created_by
)
WITH RECURSIVE demo_numbers AS (
    SELECT 4 AS n
    UNION ALL SELECT n + 1 FROM demo_numbers WHERE n < 13
)
SELECT
    CONCAT('SO-', LPAD(n, 3, '0')),
    1,
    MOD(n, 2) + 1,
    CASE MOD(n, 4)
        WHEN 0 THEN 'PendingApproval'
        WHEN 1 THEN 'Cancelled'
        WHEN 2 THEN 'Draft'
        ELSE 'Approved'
    END,
    DATE_SUB(CURRENT_DATE, INTERVAL n DAY),
    IF(MOD(n, 2) = 0, 2, 3)
FROM demo_numbers;

/* Setiap demo order memiliki item agar detail dan workflow dapat digunakan. */
INSERT INTO purchase_order_items (
    purchase_order_id, product_id, quantity, received_quantity, purchase_price
)
SELECT
    po.id,
    MOD(po.id - 1, 30) + 1,
    10,
    CASE
        WHEN po.status = 'Received' THEN 10
        WHEN po.status = 'PartiallyReceived' THEN 3
        ELSE 0
    END,
    p.purchase_price
FROM purchase_orders po
INNER JOIN products p ON p.id = MOD(po.id - 1, 30) + 1;

INSERT INTO sales_order_items (
    sales_order_id, product_id, quantity, selling_price
)
SELECT
    so.id,
    MOD(so.id - 1, 30) + 1,
    1,
    p.selling_price
FROM sales_orders so
INNER JOIN products p ON p.id = MOD(so.id - 1, 30) + 1;
