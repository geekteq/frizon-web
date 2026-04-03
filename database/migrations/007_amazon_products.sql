-- 007_amazon_products.sql
CREATE TABLE amazon_products (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug               VARCHAR(255) NOT NULL UNIQUE,
    title              VARCHAR(255) NOT NULL,
    amazon_url         VARCHAR(2048) NOT NULL,
    affiliate_url      VARCHAR(2048) NOT NULL,
    image_path         VARCHAR(512) NULL,
    amazon_description TEXT NULL,
    our_description    TEXT NULL,
    seo_title          VARCHAR(255) NULL,
    seo_description    VARCHAR(320) NULL,
    category           VARCHAR(100) NULL,
    sort_order         SMALLINT UNSIGNED DEFAULT 0,
    is_featured        TINYINT(1) NOT NULL DEFAULT 0,
    is_published       TINYINT(1) NOT NULL DEFAULT 0,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
