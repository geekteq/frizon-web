-- Frizon.org Phase 3 Schema
-- Tables: lists, list_items, list_templates

CREATE TABLE IF NOT EXISTS list_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    list_type ENUM('checklist','shopping') NOT NULL DEFAULT 'checklist',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    items_json TEXT NOT NULL COMMENT 'JSON array of default items [{text, category}]',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope_type ENUM('global','trip','stop') NOT NULL DEFAULT 'global',
    scope_id INT UNSIGNED NULL COMMENT 'trip_id or trip_stop_id depending on scope_type',
    list_type ENUM('checklist','shopping') NOT NULL DEFAULT 'checklist',
    title VARCHAR(255) NOT NULL,
    based_on_template_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (based_on_template_id) REFERENCES list_templates(id) ON DELETE SET NULL,
    INDEX idx_lists_scope (scope_type, scope_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS list_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    list_id INT UNSIGNED NOT NULL,
    item_order INT UNSIGNED NOT NULL DEFAULT 0,
    text VARCHAR(500) NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    done_at TIMESTAMP NULL,
    category VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE,
    INDEX idx_items_list (list_id),
    INDEX idx_items_order (list_id, item_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
