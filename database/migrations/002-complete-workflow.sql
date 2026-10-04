ALTER TABLE categories ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT 1;
ALTER TABLE sales_orders
    ADD COLUMN submitted_at DATETIME NULL,
    ADD COLUMN approved_at DATETIME NULL,
    ADD COLUMN fulfilled_at DATETIME NULL,
    ADD COLUMN rejection_reason VARCHAR(500) NULL;
ALTER TABLE purchase_orders
    ADD COLUMN ordered_at DATETIME NULL,
    ADD COLUMN received_at DATETIME NULL;
ALTER TABLE stock_ledger
    ADD COLUMN quantity_before INT NULL,
    ADD COLUMN quantity_after INT NULL,
    ADD COLUMN notes VARCHAR(500) NULL;
CREATE INDEX idx_product_stock_quantity ON product_stocks(warehouse_id, quantity);
CREATE INDEX idx_so_owner_status ON sales_orders(created_by, status);
