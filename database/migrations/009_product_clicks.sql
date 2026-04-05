-- 009_product_clicks.sql
-- Logs every affiliate link click through /go/{slug}

CREATE TABLE IF NOT EXISTS product_clicks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    referrer    VARCHAR(500)  DEFAULT NULL,
    user_agent  VARCHAR(500)  DEFAULT NULL,
    clicked_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id  (product_id),
    INDEX idx_clicked_at  (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
