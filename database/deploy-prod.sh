#!/bin/bash
# Deploy database to production
# Usage: bash database/deploy-prod.sh
# Requires: mysql client, prompted for password

DB_USER="geekteq1_frizon"
DB_NAME="geekteq1_frizon"

echo "=== Frizon.org — Production DB Setup ==="
echo "User: $DB_USER | DB: $DB_NAME"
echo ""

MIGRATIONS=(
    001_initial_schema.sql
    002_trips_schema.sql
    003_lists_schema.sql
    004_ai_drafts.sql
    005_fix_fk_cascade.sql
    006_places_seo_columns.sql
    007_amazon_products.sql
    008_fix_affiliate_urls.sql
    009_product_clicks.sql
    010_place_products.sql
    011_place_view_count.sql
    012_trip_teaser.sql
    013_security_controls.sql
    014_place_preview_image.sql
    015_frizze_vehicle_data.sql
)

TOTAL=${#MIGRATIONS[@]}
STEP=0

for migration in "${MIGRATIONS[@]}"; do
    STEP=$((STEP + 1))
    echo "[$STEP/$((TOTAL + 1))] Running $migration..."
    mysql -u "$DB_USER" -p "$DB_NAME" < "database/migrations/$migration"
done

echo "[$((TOTAL + 1))/$((TOTAL + 1))] Running seed.sql..."
mysql -u "$DB_USER" -p "$DB_NAME" < database/seed.sql

echo ""
echo "=== Done! ==="
echo ""
echo "IMPORTANT: Update user passwords:"
echo "  php -r \"echo password_hash('DITT-LOSENORD', PASSWORD_DEFAULT) . PHP_EOL;\""
echo "  mysql -u $DB_USER -p $DB_NAME -e \"UPDATE users SET password_hash='<hash>' WHERE username='mattias';\""
echo "  mysql -u $DB_USER -p $DB_NAME -e \"UPDATE users SET password_hash='<hash>' WHERE username='ulrica';\""
