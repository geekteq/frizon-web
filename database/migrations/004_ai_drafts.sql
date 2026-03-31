-- Frizon.org Phase 4 Schema
-- Table: ai_drafts — stores AI-generated text drafts for visits

CREATE TABLE IF NOT EXISTS ai_drafts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL,
    prompt_context TEXT NOT NULL COMMENT 'JSON-encoded context sent to AI',
    draft_text TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    INDEX idx_ai_drafts_visit (visit_id),
    INDEX idx_ai_drafts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
