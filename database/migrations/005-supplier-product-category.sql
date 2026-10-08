-- Hubungkan supplier ke satu kategori produk.
-- Aman dijalankan setelah schema versi lama maupun schema baru.
SET @has_supplier_category = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'suppliers'
      AND COLUMN_NAME = 'category_id'
);
SET @supplier_category_ddl = IF(
    @has_supplier_category = 0,
    'ALTER TABLE suppliers ADD COLUMN category_id INT NULL AFTER name',
    'SELECT 1'
);
PREPARE supplier_category_statement FROM @supplier_category_ddl;
EXECUTE supplier_category_statement;
DEALLOCATE PREPARE supplier_category_statement;

INSERT INTO categories (name, description)
SELECT 'Konsumsi', 'Makanan, minuman, dan kebutuhan konsumsi'
WHERE NOT EXISTS (
    SELECT 1 FROM categories WHERE name = 'Konsumsi'
);

UPDATE suppliers AS s
JOIN categories AS fallback_category ON fallback_category.name = 'ATK'
LEFT JOIN categories AS mapped_category
    ON mapped_category.name = CASE
        WHEN s.name = 'ATK Center' THEN 'ATK'
        WHEN s.name = 'Madura Mart' THEN 'Konsumsi'
        ELSE 'ATK'
    END
SET s.category_id = COALESCE(s.category_id, mapped_category.id, fallback_category.id);

SET @has_supplier_category_fk = (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'suppliers'
      AND CONSTRAINT_NAME = 'fk_suppliers_category'
);
SET @supplier_category_fk_ddl = IF(
    @has_supplier_category_fk = 0,
    'ALTER TABLE suppliers ADD CONSTRAINT fk_suppliers_category FOREIGN KEY (category_id) REFERENCES categories (id)',
    'SELECT 1'
);
PREPARE supplier_category_fk_statement FROM @supplier_category_fk_ddl;
EXECUTE supplier_category_fk_statement;
DEALLOCATE PREPARE supplier_category_fk_statement;

ALTER TABLE suppliers MODIFY COLUMN category_id INT NOT NULL;
