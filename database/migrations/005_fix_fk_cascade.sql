-- Fix: Add ON DELETE CASCADE to trip_stops.place_id FK
-- Without this, deleting a place that's used in a trip crashes with FK constraint error

-- Drop the existing FK constraint and re-add with CASCADE
ALTER TABLE trip_stops DROP FOREIGN KEY `2`;
ALTER TABLE trip_stops ADD CONSTRAINT fk_trip_stops_place
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE;
