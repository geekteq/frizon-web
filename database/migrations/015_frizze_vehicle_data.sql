-- Frizze internal vehicle data
-- Private admin-only data for service history, documents, receipt interpretation, and service planning.

CREATE TABLE IF NOT EXISTS frizze_vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(255) NOT NULL,
    model_year SMALLINT UNSIGNED NULL,
    base_vehicle VARCHAR(255) NULL,
    engine VARCHAR(255) NULL,
    registration VARCHAR(20) NULL,
    vin VARCHAR(50) NULL,
    odometer_km INT UNSIGNED NULL,
    owner_name VARCHAR(255) NULL,
    purchased_at DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_frizze_vehicle_registration (registration),
    INDEX idx_frizze_vehicle_vin (vin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS frizze_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    document_type ENUM('receipt','invoice','protocol','photo','manual','other') NOT NULL DEFAULT 'other',
    title VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NULL,
    file_path VARCHAR(500) NULL,
    mime_type VARCHAR(100) NULL,
    supplier VARCHAR(255) NULL,
    document_date DATE NULL,
    amount_total DECIMAL(10,2) NULL,
    currency CHAR(3) NOT NULL DEFAULT 'SEK',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES frizze_vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_frizze_documents_vehicle (vehicle_id),
    INDEX idx_frizze_documents_date (document_date),
    INDEX idx_frizze_documents_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS frizze_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NULL,
    event_type ENUM('service','repair','control','inspection','cost','note') NOT NULL DEFAULT 'note',
    event_date DATE NOT NULL,
    event_time TIME NULL,
    title VARCHAR(255) NOT NULL,
    supplier VARCHAR(255) NULL,
    odometer_km INT UNSIGNED NULL,
    amount_total DECIMAL(10,2) NULL,
    currency CHAR(3) NOT NULL DEFAULT 'SEK',
    description TEXT NULL,
    details_json JSON NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES frizze_vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES frizze_documents(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_frizze_events_vehicle_date (vehicle_id, event_date),
    INDEX idx_frizze_events_type (event_type),
    INDEX idx_frizze_events_document (document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS frizze_service_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    source_event_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    category ENUM('base_vehicle','habitation','gas','inspection','tires','other') NOT NULL DEFAULT 'other',
    status ENUM('planned','watch','done','skipped') NOT NULL DEFAULT 'planned',
    due_date DATE NULL,
    due_odometer_km INT UNSIGNED NULL,
    recurrence_months SMALLINT UNSIGNED NULL,
    recurrence_km INT UNSIGNED NULL,
    last_done_at DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES frizze_vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (source_event_id) REFERENCES frizze_events(id) ON DELETE SET NULL,
    INDEX idx_frizze_tasks_vehicle_due (vehicle_id, due_date),
    INDEX idx_frizze_tasks_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS frizze_receipt_interpretations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL DEFAULT 'anthropic',
    status ENUM('draft','reviewed','applied','rejected') NOT NULL DEFAULT 'draft',
    interpreted_json JSON NULL,
    edited_json JSON NULL,
    applied_event_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    reviewed_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (document_id) REFERENCES frizze_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (applied_event_id) REFERENCES frizze_events(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_frizze_receipts_document (document_id),
    INDEX idx_frizze_receipts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO frizze_vehicles (
    id, name, model, model_year, base_vehicle, engine, registration, vin, odometer_km, owner_name, purchased_at
) VALUES (
    1,
    'Frizze',
    'Adria Twin 600 SPT / SPT 600 Platinum',
    2017,
    'Citroën Jumper III',
    '2.0 HDI / BlueHDi diesel',
    'ZLG267',
    'VF7YD3MFC12C73353',
    100000,
    'Mattias Pettersson',
    '2022-06-14'
) ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    model = VALUES(model),
    model_year = VALUES(model_year),
    base_vehicle = VALUES(base_vehicle),
    engine = VALUES(engine),
    odometer_km = VALUES(odometer_km),
    owner_name = VALUES(owner_name),
    purchased_at = VALUES(purchased_at);

INSERT INTO frizze_events (
    vehicle_id, event_type, event_date, title, supplier, odometer_km, amount_total, currency, description, details_json
) VALUES
(
    1,
    'service',
    '2026-04-02',
    '100 000 km-service + gasoltest',
    'Torvalla LCV',
    100000,
    3743.00,
    'SEK',
    'Oljebyte med urläsning felkoder samt gasoltest.',
    JSON_ARRAY(
        'Byte motorolja enligt PSA B71 2312 / B71 2290',
        'Byte oljefilter',
        'Nollställning serviceindikator',
        'Kontroll av vätskenivåer',
        'Felkodsläsning/diagnostik',
        'Gasoltäthetskontroll enligt EN 1949'
    )
),
(
    1,
    'repair',
    '2026-04-21',
    'Främre taklucka tätad',
    NULL,
    NULL,
    NULL,
    'SEK',
    'Väntar på skyfall för att verifiera resultatet.',
    JSON_ARRAY('Tvätt med Abnet', 'Sika Cleaner 205', 'Sika Primer 210', 'Sikaflex 221')
),
(
    1,
    'service',
    '2025-02-18',
    'Ny motor, kamrem, alla vätskor och filter',
    'Torvalla LCV',
    85927,
    NULL,
    'SEK',
    'AO 128299.',
    JSON_ARRAY('Ny motor installerad', 'Kamrem bytt', 'Alla vätskor bytta', 'Alla filter bytta')
),
(
    1,
    'control',
    '2025-02-14',
    'Gasolprotokoll samt fukt/täthet',
    'Torvalla LCV',
    NULL,
    NULL,
    'SEK',
    'Godkända kontroller.',
    JSON_ARRAY('Gasol godkänd', 'Fukt- och täthetsprotokoll godkänt')
),
(
    1,
    'service',
    '2024-02-27',
    'Service hos Caravanhallen',
    'Caravanhallen',
    73650,
    NULL,
    'SEK',
    NULL,
    JSON_ARRAY('Service', 'Däckventiler', 'Bromsar kontrollerade/bytta', 'Gasoltäthet godkänd')
);

INSERT INTO frizze_service_tasks (
    vehicle_id, title, category, status, due_date, due_odometer_km, recurrence_months, recurrence_km, last_done_at, notes
) VALUES
(
    1,
    'Årlig service: olja, oljefilter, diagnostik och nivåkontroll',
    'base_vehicle',
    'planned',
    '2027-04-02',
    115000,
    12,
    15000,
    '2026-04-02',
    'Senast gjort hos Torvalla LCV 2026-04-02.'
),
(
    1,
    'Gasoltäthetskontroll enligt EN 1949',
    'gas',
    'planned',
    '2027-04-02',
    NULL,
    12,
    NULL,
    '2026-04-02',
    'Senast godkänd 2026-04-02.'
),
(
    1,
    'Fukttest / habitation check',
    'habitation',
    'watch',
    '2026-12-31',
    NULL,
    12,
    NULL,
    '2025-02-14',
    'Saknas i nuvarande historik för 2026.'
),
(
    1,
    'Bromsvätska',
    'base_vehicle',
    'planned',
    '2027-02-18',
    NULL,
    24,
    NULL,
    '2025-02-18',
    'Alla vätskor byttes 2025 i samband med motor/kamrem.'
),
(
    1,
    'Kupéfilter',
    'base_vehicle',
    'planned',
    '2027-02-18',
    NULL,
    24,
    NULL,
    '2025-02-18',
    'Alla filter byttes 2025.'
);
