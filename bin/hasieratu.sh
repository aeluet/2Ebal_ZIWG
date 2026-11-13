#!/usr/bin/env bash
# Ingurune osoa hutsetik abiarazten du (README-ko "Hutsetik abiatu" pausoak).
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    cp .env.example .env
    echo ".env sortuta. Gogoratu ODOO_API_KEY betetzea (ikus README.md)."
fi

docker compose up -d --build
docker compose exec -T app composer install

if ! grep -q '^APP_KEY=base64' .env; then
    docker compose exec -T app php artisan key:generate
fi

docker compose exec -T app php artisan migrate --force
./bin/odoo-hasieratu.sh

echo
echo "Prest:"
echo "  Laravel  http://localhost:8000"
echo "  Odoo     http://localhost:8069  (admin / admin)"
echo "  Mailpit  http://localhost:8025"
