-- 010_place_products.sql
-- Links amazon_products to places for contextual "used here" product recommendations

CREATE TABLE IF NOT EXISTS place_products (
    place_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    note        VARCHAR(255)  DEFAULT NULL,
    sort_order  TINYINT UNSIGNED DEFAULT 0,
    PRIMARY KEY (place_id, product_id),
    FOREIGN KEY (product_id) REFERENCES amazon_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
