# Bergarako Antzokia ↔ Odoo

0613 Zerbitzari-inguruneko web-garapena (DAW 2. maila) moduluko klaseetarako repoa.
Laravel aplikazio bat Odoo 19-rekin konektatzen dugu, Odoo-ren **JSON-2 API**-a erabiliz.

Saio bakoitzaren amaierako egoera git tag batean dago. Atzean geratu bazara:

```bash
git checkout s01-amaiera     # edo s00-hasiera, s02-amaiera, s03-amaiera...
```

## Behar duzuna

- Docker eta Docker Compose
- Git

Ez duzu PHP edo Composer zure ordenagailuan instalatu beharrik: dena edukiontzien barruan exekutatzen da.

## Zerbitzuak

| Zerbitzua  | Helbidea                | Zertarako                                    |
|------------|-------------------------|----------------------------------------------|
| `app`      | http://localhost:8000   | Laravel aplikazioa (PHP 8.5 + Apache)         |
| `odoo`     | http://localhost:8069   | Odoo 19 (erabiltzailea `admin`, pasahitza `admin`) |
| `mailpit`  | http://localhost:8025   | Posta faltsua (3. astean erabiliko dugu)      |
| `mysql`    | localhost:3306          | Laravel-en datu-basea                         |
| `odoo-db`  | —                       | Odoo-ren PostgreSQL-a                         |
| `queue`    | —                       | Ilarako langilea (3. astean, `queue` profila) |
| `scheduler`| —                       | Programatzailea (4. astean, `scheduler` profila) |

Edukiontzien artean Odoo `http://odoo:8069` helbidean dago (ez `localhost`).

## Hutsetik abiatu

Script bakarrarekin:

```bash
./bin/hasieratu.sh
```

Edo pausoz pauso:

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
./bin/odoo-hasieratu.sh     # lehen aldian bakarrik
```

`bin/odoo-hasieratu.sh`-k Odoo-n `antzokia` datu-basea sortzen du, `contacts` eta `product`
moduluekin eta **demo datuekin** (partner eta produktu batzuk prest izateko).

### Arazo ohikoak

- **3306 portua okupatuta** (zure ordenagailuan MySQL bat dago martxan): jarri `.env`-n
  `MYSQL_PORT=3307` eta exekutatu berriro `docker compose up -d`.
- **Baimen-erroreak `storage/`-n edo `vendor/`-en (Linux)**: edukiontziek 1000:1000 erabiltzailea
  erabiltzen dute. Zure erabiltzailea bestelakoa bada (`id -u`), jarri `.env`-n `UID` eta `GID`.
- **Odoo-k denbora behar du abiatzeko**: `app`-ek Odoo osasuntsu egon arte itxaroten du.
  Ikusi egoera: `docker compose ps`.

## Odoo-ren API gakoa sortu

JSON-2 API-ak API gako bat behar du. **Ez da automatikoki sortzen**: zuk sortu behar duzu.

1. Sartu http://localhost:8069 helbidean (`admin` / `admin`).
2. Goian eskuinean, egin klik zure avatarrean → **My Preferences**.
3. Joan **Security** fitxara.
4. **API Keys** atalean, sakatu **Add API Key**.
5. Odoo-k zure pasahitza eskatuko dizu berriro (`admin`) → **Confirm Password**.
6. Idatzi deskribapen bat (adibidez `antzokia-laravel`) eta aukeratu iraupena (**3 Months** gehienez).
7. Sakatu **Generate key** eta **kopiatu gakoa**. Ez da berriro erakutsiko!
8. Itsatsi `.env` fitxategian:

   ```
   ODOO_API_KEY=itsatsi-hemen-gakoa
   ```

Gogoratu: gakoak gehienez 3 hilabete irauten du. Iraungitzen bada, sortu beste bat.
Gakoa **inoiz** ez da kodean edo git-en jartzen: `.env`-n bakarrik.

## API-aren dokumentazioa

Odoo-n saioa hasita, ireki http://localhost:8069/doc. Hor modelo bakoitzaren eremuak eta metodoak
ikus daitezke (adibidez `res.partner`).

Lehen deiak curl bidez: [docs/curl-adibideak.md](docs/curl-adibideak.md).

Laravel-etik (`tinker`-en):

```bash
docker compose exec app php artisan tinker
> app(\App\Services\Odoo\OdooClient::class)->call('res.partner', 'search_read', ['domain' => [], 'fields' => ['name', 'email'], 'limit' => 5]);
```

## Komando erabilgarriak

```bash
docker compose ps                         # zerbitzuen egoera
docker compose logs -f odoo               # Odoo-ren logak
docker compose exec app php artisan tinker
docker compose stop odoo                  # Odoo gelditu (erroreak probatzeko)
docker compose start odoo
docker compose down                       # dena gelditu (datuak mantentzen dira)
docker compose down -v                    # dena ezabatu, datuak barne
```
