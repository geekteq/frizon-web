-- 011_place_view_count.sql
-- Adds a simple request counter to the places table

ALTER TABLE places ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0;
