-- 018_visit_products.sql
-- Per-visit Amazon product associations. Mirrors place_products but scoped
-- to a single visit ("what we used at this specific stay").

CREATE TABLE IF NOT EXISTS visit_products (
    visit_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    note        VARCHAR(255) DEFAULT NULL,
    sort_order  TINYINT UNSIGNED DEFAULT 0,
    PRIMARY KEY (visit_id, product_id),
    FOREIGN KEY (visit_id)   REFERENCES visits(id)           ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES amazon_products(id)  ON DELETE CASCADE,
    INDEX idx_visit_products_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
