-- Seed users (password: "frizon2026" bcrypt hash)
-- Generate real hash: php -r "echo password_hash('frizon2026', PASSWORD_BCRYPT) . PHP_EOL;"
-- Placeholder hashes below — replace after setup.
INSERT INTO users (username, email, password_hash, display_name) VALUES
('mattias', 'mattias@frizon.org', '$2y$10$PLACEHOLDER_HASH_REPLACE_ME_AFTER_SETUP_mattias000000000', 'Mattias'),
('ulrica', 'ulrica@frizon.org', '$2y$10$PLACEHOLDER_HASH_REPLACE_ME_AFTER_SETUP_ulrica0000000000', 'Ulrica');

-- Seed example places
INSERT INTO places (slug, name, lat, lng, country_code, place_type, created_by) VALUES
('hammaro-stallplats-a1b2c3', 'Hammarö Ställplats', 59.3299000, 13.5227000, 'SE', 'stellplatz', 1),
('cafe-sjokanten-d4e5f6', 'Cafe Sjökanten', 58.7530000, 17.0086000, 'SE', 'fika', 1),
('camping-le-grand-large-g7h8i9', 'Camping Le Grand Large', 48.8400000, -1.5050000, 'FR', 'camping', 1);

-- Seed a visit
INSERT INTO visits (place_id, user_id, visited_at, raw_note, price_level, would_return, suitable_for) VALUES
(1, 1, '2025-06-15', 'Lugnt och fint. Nära vattnet. Bra service.', 'low', 'yes', 'husbilar,hundar,familjer');

-- Seed ratings
INSERT INTO visit_ratings (visit_id, location_rating, calmness_rating, service_rating, value_rating, return_value_rating, total_rating_cached) VALUES
(1, 4, 5, 3, 4, 5, 4.2);

-- Seed tags
INSERT INTO place_tags (place_id, tag) VALUES
(1, 'vid vatten'),
(1, 'lugnt'),
(2, 'havsutsikt'),
(2, 'fika');
