-- Frizon.org Phase 1 Schema
-- Tables: users, places, place_tags, visits, visit_images, visit_ratings

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS places (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    lat DECIMAL(10, 7) NOT NULL,
    lng DECIMAL(10, 7) NOT NULL,
    address_text VARCHAR(500) NULL,
    country_code CHAR(2) NULL,
    place_type ENUM(
        'breakfast','lunch','dinner','fika','sight',
        'shopping','stellplatz','wild_camping','camping'
    ) NOT NULL DEFAULT 'stellplatz',
    public_allowed TINYINT(1) NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_toplisted TINYINT(1) NOT NULL DEFAULT 0,
    toplist_order INT UNSIGNED NULL,
    default_public_text TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_places_type (place_type),
    INDEX idx_places_country (country_code),
    INDEX idx_places_public (public_allowed),
    INDEX idx_places_coords (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS place_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_id INT UNSIGNED NOT NULL,
    tag VARCHAR(100) NOT NULL,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_place_tag (place_id, tag),
    INDEX idx_tags_tag (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    visited_at DATE NOT NULL,
    raw_note TEXT NULL,
    plus_notes TEXT NULL,
    minus_notes TEXT NULL,
    tips_notes TEXT NULL,
    price_level ENUM('free','low','medium','high') NULL,
    would_return ENUM('yes','maybe','no') NULL,
    suitable_for VARCHAR(500) NULL COMMENT 'Comma-delimited freetext values',
    things_to_note TEXT NULL,
    ai_draft_id INT UNSIGNED NULL,
    approved_public_text TEXT NULL,
    ready_for_publish TINYINT(1) NOT NULL DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_visits_place (place_id),
    INDEX idx_visits_user (user_id),
    INDEX idx_visits_date (visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL UNIQUE,
    location_rating TINYINT UNSIGNED NULL CHECK (location_rating BETWEEN 1 AND 5),
    calmness_rating TINYINT UNSIGNED NULL CHECK (calmness_rating BETWEEN 1 AND 5),
    service_rating TINYINT UNSIGNED NULL CHECK (service_rating BETWEEN 1 AND 5),
    value_rating TINYINT UNSIGNED NULL CHECK (value_rating BETWEEN 1 AND 5),
    return_value_rating TINYINT UNSIGNED NULL CHECK (return_value_rating BETWEEN 1 AND 5),
    total_rating_cached DECIMAL(2,1) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    image_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    alt_text VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    INDEX idx_images_visit (visit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
