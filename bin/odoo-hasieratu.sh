#!/usr/bin/env bash
# Odoo-ren "antzokia" datu-basea sortzen du, demo datuekin:
#   - contacts: kontaktuak (res.partner)
#   - product:  produktuak (product.product, "zuk zeuk" ariketetarako)
# Lehen aldian bakarrik exekutatu behar da. Berriro exekutatuz gero, ez du ezer egiten.
# Erabiltzailea: admin / Pasahitza: admin
set -euo pipefail
cd "$(dirname "$0")/.."

DB=antzokia

if docker compose exec -T odoo-db psql -U odoo -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$DB'" | grep -q 1; then
    echo "'$DB' datu-basea badago jada. Ez da ezer egin."
    exit 0
fi

echo "'$DB' datu-basea sortzen (minutu batzuk behar ditu)..."
docker compose exec -T odoo odoo -c /etc/odoo/odoo.conf \
    -d "$DB" -i contacts,product --with-demo \
    --stop-after-init --no-http

echo "Eginda. Sartu http://localhost:8069 helbidean (admin / admin)."
