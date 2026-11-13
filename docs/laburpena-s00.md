# S00 laburpena: `s00-hasiera` (ikasleek klonatzen dutena)

Laravel 13 garbia (framework v13.42.0, PHP 8.5) + docker-compose osoa + `bin/` scriptak + README.
Laravel-en ez dago Odoo-rekin lotutako koderik.

## Sortutako / aldatutako fitxategiak

| Fitxategia | Zer |
|---|---|
| (Laravel skeleton) | `composer create-project laravel/laravel:^13` (skeleton-aren `CLAUDE.md`, `AGENTS.md` eta `README.md` kenduta) |
| `Dockerfile` | PHP 8.5 + Apache irudia, docroot `public/`, 8080 portua |
| `docker-compose.yml` | app, queue, scheduler, mysql, odoo, odoo-db, mailpit |
| `odoo/odoo.conf` | Odoo-ren konfigurazioa, `dbfilter` DB bakarrera |
| `.env.example` | MySQL, Mailpit eta `ODOO_*` aldagaiak |
| `bin/odoo-hasieratu.sh` | `antzokia` DB-a sortu, `contacts` + `product`, demo datuekin |
| `bin/hasieratu.sh` | Dena hutsetik |
| `README.md` | Euskarazko gida, API gakoa sortzeko pausoak barne |

## Kodea (idazteko ordenan)

### `Dockerfile`

```dockerfile
# Laravel aplikazioaren irudia: PHP 8.5 + Apache.
# app, queue eta scheduler zerbitzuek irudi hau bera erabiltzen dute.
FROM php:8.5-apache

# Sistemako paketeak eta PHP luzapenak (MySQL, zip, intl).
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl bcmath \
    && rm -rf /var/lib/apt/lists/*

# Composer, irudi ofizialetik kopiatuta.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: docroot-a public/ karpeta da eta mod_rewrite gaituta (Laravel-en ibilbideak).
# 8080 portuan entzuten du, root ez den erabiltzaile batekin exekutatu ahal izateko.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!Listen 80$!Listen 8080!' /etc/apache2/ports.conf \
    && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:8080>!' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

# Composer-ek bere cachea idazteko karpeta (edozein erabiltzailerekin).
ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html
EXPOSE 8080
```

### `docker-compose.yml`

```yaml
# Bergarako Antzokia ↔ Odoo: garapen-ingurunea.
#
# Erabiltzailea: edukiontziek zure erabiltzailearen UID/GID-arekin idazten dute
# (lehenetsia 1000:1000, Ubuntun ohikoa). Bestelakoa bada, jarri .env-n UID eta GID.

x-laravel: &laravel
  build: .
  image: antzokia-app
  user: "${UID:-1000}:${GID:-1000}"
  volumes:
    - ./:/var/www/html
  depends_on:
    mysql:
      condition: service_healthy
    odoo:
      condition: service_healthy

services:
  app:
    <<: *laravel
    ports:
      - "8000:8080"

  # Ilarako langilea. 3. astean aktibatzen da: docker compose --profile queue up -d
  queue:
    <<: *laravel
    profiles: ["queue"]
    command: php artisan queue:work --tries=3 --backoff=10

  # Programatzailea. 4. astean aktibatzen da: docker compose --profile scheduler up -d
  scheduler:
    <<: *laravel
    profiles: ["scheduler"]
    command: php artisan schedule:work

  mysql:
    image: mysql:8.4
    environment:
      MYSQL_DATABASE: "${DB_DATABASE:-antzokia}"
      MYSQL_USER: "${DB_USERNAME:-antzokia}"
      MYSQL_PASSWORD: "${DB_PASSWORD:-sekretua}"
      MYSQL_ROOT_PASSWORD: "${DB_PASSWORD:-sekretua}"
    ports:
      - "${MYSQL_PORT:-3306}:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_PASSWORD:-sekretua}"]
      interval: 5s
      timeout: 5s
      retries: 20

  odoo:
    image: odoo:19
    depends_on:
      odoo-db:
        condition: service_healthy
    ports:
      - "8069:8069"
    volumes:
      - ./odoo/odoo.conf:/etc/odoo/odoo.conf:ro
      - odoo-data:/var/lib/odoo
    healthcheck:
      test: ["CMD", "curl", "-fs", "http://localhost:8069/web/health"]
      interval: 10s
      timeout: 5s
      retries: 30

  odoo-db:
    image: postgres:16
    environment:
      POSTGRES_DB: postgres
      POSTGRES_USER: odoo
      POSTGRES_PASSWORD: odoo
    volumes:
      - odoo-db-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U odoo"]
      interval: 5s
      timeout: 5s
      retries: 20

  # Posta-zerbitzari faltsua: UI 8025 portuan, SMTP 1025 portuan. 3. astean erabiltzen da.
  mailpit:
    image: axllent/mailpit
    ports:
      - "8025:8025"
      - "1025:1025"

volumes:
  mysql-data:
  odoo-data:
  odoo-db-data:
```

### `odoo/odoo.conf`

```ini
[options]
addons_path = /mnt/extra-addons
data_dir = /var/lib/odoo

; PostgreSQL konexioa (docker-compose-ko odoo-db zerbitzua)
db_host = odoo-db
db_port = 5432
db_user = odoo
db_password = odoo

; DB bakarra: antzokia. Beste guztiak ezkutatuta.
dbfilter = ^antzokia$

; Datu-baseen kudeatzailearen pasahitz nagusia (garapenerako bakarrik!)
admin_passwd = admin
```

### `.env.example`

```dotenv
APP_NAME="Bergarako Antzokia"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=eu
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_ES

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# MySQL (docker-compose-ko mysql zerbitzua)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=antzokia
DB_USERNAME=antzokia
DB_PASSWORD=sekretua

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

# Mailpit (docker-compose-ko mailpit zerbitzua). UI: http://localhost:8025
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="antzokia@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"

# Odoo JSON-2 API. Gakoa Odoo-ren interfazetik sortzen da (ikus README.md).
ODOO_URL=http://odoo:8069
ODOO_DB=antzokia
ODOO_API_KEY=

# Edukiontzien erabiltzailea (docker-compose). Ubuntun normalean 1000:1000.
# Zurea jakiteko: id -u eta id -g
# UID=1000
# GID=1000

# Ostalariko MySQL portua (3306 okupatuta badago, adib. 3307)
# MYSQL_PORT=3306
```

### `bin/odoo-hasieratu.sh`

```bash
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
```

### `bin/hasieratu.sh`

```bash
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
```

## Egiaztapena

Klon garbi batean, CLAUDE.md-ko "Hutsetik abiatu" pausoak eskuz exekutatuta:

```
$ docker compose up -d --build            → 5 zerbitzu martxan (mysql, odoo, odoo-db, mailpit healthy)
$ docker compose exec app composer install
$ docker compose exec app php artisan key:generate
   INFO  Application key set successfully.
$ docker compose exec app php artisan migrate
  0001_01_01_000000_create_users_table .......... DONE
  0001_01_01_000001_create_cache_table .......... DONE
  0001_01_01_000002_create_jobs_table ........... DONE
$ ./bin/odoo-hasieratu.sh
Eginda. Sartu http://localhost:8069 helbidean (admin / admin).
$ ./bin/odoo-hasieratu.sh                 (bigarren aldiz)
'antzokia' datu-basea badago jada. Ez da ezer egin.

$ curl -s -o /dev/null -w '%{http_code}' localhost:8000          → 200 (<title>Bergarako Antzokia)
$ curl -s -o /dev/null -w '%{http_code}' localhost:8069/web/login → 200
$ curl -s -o /dev/null -w '%{http_code}' localhost:8025          → 200 (Mailpit)
$ curl -s -o /dev/null -w '%{http_code}' localhost:8069/doc       → 303 (login-era); saioa hasita → 200 "Odoo Runtime Doc"
$ php artisan --version                    → Laravel Framework 13.42.0 (PHP 8.5.12)
Demo datuak: 41 res.partner, 44 product.product
```

## Arazoak eta ikasleen akats posibleak

- **3306 portua okupatuta** ostalarian (MySQL lokala) → `.env`-n `MYSQL_PORT=3307`. Docker-ek
  "port is already allocated" / "address already in use" erakusten du eta `app` ez da abiatzen.
- **Fitxategien jabea (Linux)**: edukiontziak `1000:1000` erabiltzailearekin exekutatzen dira, `vendor/`
  eta `storage/` root-en jabetzakoak ez izateko. UID desberdina bada → `.env`-n `UID`/`GID`.
  Horregatik Apache-k 8080 portuan entzuten du barruan (root gabe ezin da 80 erabili); kanpoan 8000.
- **`/doc` orriak saioa behar du**: lehenik `localhost:8069`-n saioa hasi (admin/admin).
- **Odoo-k 20-40 s behar ditu** osasuntsu egoteko; `app` ez da abiatzen hura prest egon arte.
- Odoo 19-n demo datuak **ez** dira lehenetsiz instalatzen: `--with-demo` behar da.
- Laravel 13-ko skeleton-ak `CLAUDE.md` eta `AGENTS.md` dakartza (Laravel Boost); kendu egin dira.
