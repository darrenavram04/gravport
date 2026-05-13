#!/bin/bash
# Fix WebMap data: drop testing schema and re-import gravport dump
set -e

DUMP="/var/www/geoportal/build/dump_gravport.sql.gz"
DB="geoportal"

echo "=== [1/4] Drop testing schema ==="
sudo -u postgres psql -d $DB -c "DROP SCHEMA IF EXISTS testing CASCADE;"
echo "Dropped."

echo ""
echo "=== [2/4] Import gravport dump (takes ~2-5 minutes) ==="
gunzip -c "$DUMP" | sudo -u postgres psql -d $DB
echo "Import done."

echo ""
echo "=== [3/4] Grant permissions to geoportal_user ==="
sudo -u postgres psql -d $DB -c "
GRANT ALL ON SCHEMA testing TO geoportal_user;
GRANT ALL ON ALL TABLES IN SCHEMA testing TO geoportal_user;
GRANT ALL ON ALL SEQUENCES IN SCHEMA testing TO geoportal_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA testing GRANT ALL ON TABLES TO geoportal_user;
"
echo "Permissions granted."

echo ""
echo "=== [4/4] Row counts ==="
sudo -u postgres psql -d $DB -c "
SELECT 'faa_l1_points' AS tbl, COUNT(*) AS rows FROM testing.faa_l1_points
UNION ALL SELECT 'cba_l1_points', COUNT(*) FROM testing.cba_l1_points
UNION ALL SELECT 'faa_l2_raster', COUNT(*) FROM testing.faa_l2_raster
UNION ALL SELECT 'cba_l2_raster', COUNT(*) FROM testing.cba_l2_raster
UNION ALL SELECT 'AOI_Jawa_Bali', COUNT(*) FROM testing.\"AOI Jawa_Bali\";
"

echo ""
echo "=== Restarting Apache ==="
systemctl restart apache2
echo "Done! WebMap data should now be loaded."
