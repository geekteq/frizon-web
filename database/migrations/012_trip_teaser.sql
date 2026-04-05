-- 012_trip_teaser.sql
-- Opt-in public teaser for upcoming trips shown on the homepage.
-- Exposes only the teaser_text and approximate start month — no stop details.

ALTER TABLE trips
    ADD COLUMN public_teaser TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN teaser_text   VARCHAR(500) DEFAULT NULL;
