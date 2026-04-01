-- 006_places_seo_columns.sql
ALTER TABLE places
    ADD COLUMN meta_description VARCHAR(255) NULL AFTER default_public_text,
    ADD COLUMN faq_content JSON NULL AFTER meta_description;
