ALTER TABLE places
    ADD COLUMN preview_image_id INT UNSIGNED NULL DEFAULT NULL AFTER toplist_order,
    ADD CONSTRAINT fk_places_preview_image
        FOREIGN KEY (preview_image_id) REFERENCES visit_images(id)
        ON DELETE SET NULL;
