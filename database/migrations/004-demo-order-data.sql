START TRANSACTION;

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
FROM demo_numbers
WHERE NOT EXISTS (
    SELECT 1 FROM purchase_orders
    WHERE order_number = CONCAT('PO-', LPAD(demo_numbers.n, 3, '0'))
);

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
FROM demo_numbers
WHERE NOT EXISTS (
    SELECT 1 FROM sales_orders
    WHERE order_number = CONCAT('SO-', LPAD(demo_numbers.n, 3, '0'))
);

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
INNER JOIN products p ON p.id = MOD(po.id - 1, 30) + 1
LEFT JOIN purchase_order_items poi
    ON poi.purchase_order_id = po.id AND poi.product_id = p.id
WHERE poi.id IS NULL;

INSERT INTO sales_order_items (
    sales_order_id, product_id, quantity, selling_price
)
SELECT so.id, MOD(so.id - 1, 30) + 1, 1, p.selling_price
FROM sales_orders so
INNER JOIN products p ON p.id = MOD(so.id - 1, 30) + 1
LEFT JOIN sales_order_items soi
    ON soi.sales_order_id = so.id AND soi.product_id = p.id
WHERE soi.id IS NULL;

COMMIT;
