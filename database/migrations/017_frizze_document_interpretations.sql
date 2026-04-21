-- Generic AI interpretations for private Frizze documents.
-- Kept separate from the early receipt-specific table so all document types can use it.

CREATE TABLE IF NOT EXISTS frizze_document_interpretations (
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
    INDEX idx_frizze_doc_interpretations_document (document_id),
    INDEX idx_frizze_doc_interpretations_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
