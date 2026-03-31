-- Frizon.org Phase 2 Schema
-- Tables: trips, trip_stops, trip_route_segments

CREATE TABLE IF NOT EXISTS trips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    intro_text TEXT NULL,
    public_summary TEXT NULL,
    cover_image_path VARCHAR(500) NULL,
    status ENUM('planned','ongoing','finished') NOT NULL DEFAULT 'planned',
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_trips_status (status),
    INDEX idx_trips_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trip_stops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    place_id INT UNSIGNED NOT NULL,
    stop_order INT UNSIGNED NOT NULL DEFAULT 0,
    stop_type ENUM(
        'breakfast','lunch','dinner','fika','sight',
        'shopping','stellplatz','wild_camping','camping'
    ) NULL,
    planned_at DATETIME NULL,
    arrival_at DATETIME NULL,
    departure_at DATETIME NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (place_id) REFERENCES places(id),
    INDEX idx_stops_trip (trip_id),
    INDEX idx_stops_order (trip_id, stop_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trip_route_segments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    from_stop_id INT UNSIGNED NOT NULL,
    to_stop_id INT UNSIGNED NOT NULL,
    distance_km DECIMAL(8,2) NULL,
    provider_eta_minutes INT UNSIGNED NULL,
    eta_95_minutes INT UNSIGNED NULL,
    geometry MEDIUMTEXT NULL COMMENT 'Encoded polyline or GeoJSON',
    provider_name VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (from_stop_id) REFERENCES trip_stops(id) ON DELETE CASCADE,
    FOREIGN KEY (to_stop_id) REFERENCES trip_stops(id) ON DELETE CASCADE,
    INDEX idx_segments_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
