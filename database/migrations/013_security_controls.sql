-- Phase 9: Security controls
-- Adds:
--   1. users.is_admin for explicit admin gating
--   2. security_audit_log for high-value auth/admin event logging

ALTER TABLE users
    ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER display_name;

UPDATE users
SET is_admin = 1;

CREATE TABLE IF NOT EXISTS security_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    event_type VARCHAR(100) NOT NULL,
    request_path VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    details_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_security_audit_event (event_type),
    INDEX idx_security_audit_created (created_at),
    INDEX idx_security_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
