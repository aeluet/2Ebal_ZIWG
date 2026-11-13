# Bergarako Antzokia ↔ Odoo — Klaseko erreferentzia-repoa

## Zer da repo hau

"0613 Zerbitzari-inguruneko web-garapena" moduluko (DAW 2. maila) **live-coding klaseetarako erreferentzia-repoa**.
Irakasleak klasean kodetzen du eta ikasleek jarraitzen diote. Saio bakoitzaren amaierako egoera **git tag** batean gordetzen da, atzean geratzen den ikasleak `git checkout sNN-amaiera` eginda jarraitu ahal izateko.

Hilabeteko gaiak: Odoo 19 JSON-2 API (irakurri eta idatzi), PHPUnit, Email, Jobs/Queue, Scheduler.
Live-coding guztia `res.partner` modeloaren gainean egiten da. Ikasleek "zuk zeuk" ariketetan patroi bera `product.product`-ekin errepikatzen dute.

## Lan-arauak (Claude Code-rentzat)

1. **Exekutatu eta egiaztatu dena.** Tag bat ez da sortzen bere egiaztapen-zerrenda (behean) osorik exekutatu eta gainditu arte. Ez asmatu irteerarik.
2. **Kodea live-coding-erako da**: metodo laburrak, izen argiak, abstrakzio gutxi. Irakasle batek 2–3 orduan idatzi behar du, azalduz. Dudarik baduzu, aukeratu sinpleena.
3. **Ez aurreratu gaiak.** 1. asteko tag-etan EZ dago Job-ik, testik, Mailable-ik edo scheduler-ik. Arkitekturak horiek gero gehitzea ahalbidetu behar du, baina orain ez dira agertzen.
4. **Odoo-ren eremu eta metodo guztiak egiaztatu** martxan dagoen Odoo-ren `/doc` orrian edo benetako dei batekin, erabili aurretik.
5. Tag bakoitza **hutsetik eraikitzeko modukoa** izan behar da (ikus "Hutsetik abiatu").
6. Galderarik baduzu edo arau batek beste bat kontraesaten badu, gelditu eta galdetu.

## Hizkuntza eta kode-konbentzioak

- Iruzkinak, Blade-ko testuak, flash mezuak, commit mezuak eta dokumentazioa: **euskaraz**.
- Domeinuko izenak euskaraz: `Bezeroa` modeloa, `bezeroak` taula, `GordeBezeroaRequest`, `OdooPartnerController`.
- Laravel/PHP konbentzioak ingelesez mantentzen dira: `index`, `store`, `handle`, `$fillable`...
- MVC zorrotza: kontrolatzaile meheak, balidazioa `FormRequest`-etan, Odoo-rako sarbide guztia `app/Services/Odoo/`-n.
- PHP: `declare(strict_types=1);` ez erabili (ikasleentzat zarata). Type hint-ak bai.
- Ez gehitu pakete gehigarririk (Pest, Livewire, Breeze...) baimenik gabe.

## Ingurunea

- Ostalaria: Ubuntu + Docker Compose. Etorkizunean AWS-ra hedatuko da (ez da hilabete honetako gaia).
- Laravel: azken bertsio egonkorra. PHP: Laravel bertsio horrek eskatzen duena (8.3+).
- Datu-basea: **MySQL** garapenean. Testak: **SQLite `:memory:`** (`phpunit.xml`-n), 2. astean erabiliko dira.

### docker-compose zerbitzuak

| Zerbitzua | Irudia | Portua | Oharrak |
|---|---|---|---|
| `app` | `Dockerfile` propioa (php-apache, `public/` docroot) | 8000 | Kodea bolumen gisa muntatuta |
| `queue` | `app`-en irudi bera | — | `php artisan queue:work --tries=3 --backoff=10`. **Profila: `queue`** (3. astean aktibatzen da) |
| `scheduler` | `app`-en irudi bera | — | `php artisan schedule:work`. **Profila: `scheduler`** (4. astean aktibatzen da) |
| `mysql` | `mysql:8.4` | 3306 | healthcheck-arekin |
| `odoo` | `odoo:19` | 8069 | `odoo.conf` muntatuta, `dbfilter` DB bakarrera |
| `odoo-db` | `postgres:16` (edo Odoo 19-k onartzen duena) | — | Odoo-rena bakarrik |
| `mailpit` | `axllent/mailpit` | 8025 (UI), 1025 (SMTP) | 3. astean erabiliko da, baina hasieratik dago |

- `app`, `queue` eta `scheduler` zerbitzuek `.env` bera erabiltzen dute.
- Edukiontzien artean, `app`-etik Odoo-ra: `http://odoo:8069`.
- `depends_on` + `condition: service_healthy` MySQL eta Odoo-rentzat.

### .env.example (Odoo zatia)

```
ODOO_URL=http://odoo:8069
ODOO_DB=antzokia
ODOO_API_KEY=
```

`config/services.php`-n `odoo` gakoa (`url`, `db`, `key`). Gakoa **inoiz** ez kodean.

### Odoo-ren hasieraketa

- `bin/odoo-hasieratu.sh`: `antzokia` DB-a sortzen du eta `contacts` modulua instalatzen du (demo daturik gabe edo demo datuekin; galdetu irakasleari). `product.product`-erako `product` modulua ere bai (zuk zeuk ariketetarako).
- **API gakoa EZ da automatikoki sortzen**: S1 saioan eskuz sortzen da Odoo-ren interfazetik (hori da ikasgaia). README-an pausoak azaldu, pantaila-irudirik gabe.
- Kontuan izan: API gakoek gehienez 3 hilabeteko iraupena dute.

### Hutsetik abiatu (tag guztietan funtzionatu behar du)

```
git checkout sNN-amaiera
cp .env.example .env        # eta ODOO_API_KEY bete
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
./bin/odoo-hasieratu.sh     # lehen aldian bakarrik
```

Script bat (`bin/hasieratu.sh`) dena batera egiteko.

## Odoo JSON-2 API: oinarriak

- `POST {ODOO_URL}/json/2/{model}/{method}`
- Goiburuak: `Authorization: bearer <API_KEY>`, `Content-Type: application/json`, `X-Odoo-Database: <db>`, `User-Agent: antzokia-laravel`.
- Gorputza: JSON objektua, metodoaren parametroekin (`domain`, `fields`, `limit`, `ids`, `vals_list`...). **Egiaztatu parametro-izen zehatzak `/doc` orrian.**
- Arrakasta: 200 + emaitza JSON. Errorea: 4xx/5xx + JSON objektua (`name`, `message`, `arguments`, `context`, `debug`).
- Dei bakoitza bere transakzioan exekutatzen da.

---

## 1. asteko tag-ak (orain eraiki behar direnak)

### `s00-hasiera`: ikasleek klonatzen dutena
- Laravel garbia + docker-compose osoa + `bin/` scriptak + README (euskaraz).
- Odoo martxan eta `/doc` eskuragarri. Laravel-en ez dago Odoo-rekin lotutako koderik.
- `ODOO_*` aldagaiak `.env.example`-n, hutsik.

**Egiaztapena:** `localhost:8000` Laravel-en hasiera-orria · `localhost:8069` Odoo · `localhost:8025` Mailpit · `migrate` errorerik gabe.

### `s01-amaiera` (3h saioa): Docker + Odoo + lehen deia
- `config/services.php`-n `odoo` sarrera.
- `app/Services/Odoo/OdooClient.php`, metodo bakarrarekin: `call(string $model, string $method, array $params = []): array`
  - `Http::withToken()` + `X-Odoo-Database` + `baseUrl()` + `timeout(10)` + `->throw()->json()`.
- `docs/curl-adibideak.md`: `res.partner` `search_read` curl bidez (S1en lehen zatian erabiltzen da, Laravel ukitu aurretik).
- `tinker`-en `app(OdooClient::class)->call('res.partner', 'search_read', [...])` funtzionatzen du.

**Egiaztapena:** curl adibideak benetan exekutatu eta partner-ak itzultzen dituzte · tinker-eko deiak partner-ak itzultzen ditu · API gaki okerrarekin 401 jasotzen da eta salbuespena jaurtitzen da.

### `s02-amaiera` (2h saioa): irakurri eta erakutsi
- `app/Services/Odoo/OdooException.php`: Odoo-ren errore-JSONa (`message`, HTTP egoera) gordetzen du.
- `OdooClient`: `call()`-ek `RequestException` eta `ConnectionException` harrapatu eta `OdooException` jaurtitzen du. Laguntzaileak: `searchRead(string $model, array $domain, array $fields, int $limit = 50)`.
- `OdooPartnerController@index` → `resources/views/partnerrak/index.blade.php` (taula: izena, emaila, telefonoa).
- Odoo erorita dagoenean: errore-orri edo mezu atsegina, ez Laravel-en stack trace-a.
- Ibilbidea: `GET /partnerrak`.

**Egiaztapena:** `/partnerrak`-ek Odoo-ko partner-ak erakusten ditu · `docker compose stop odoo` → mezu atsegina · gaki okerrarekin → mezu atsegina (ez 500 gordina).

### `s03-amaiera` (2h saioa): idatzi (nahita SINKRONOA)
- Migrazioa `bezeroak`: `id`, `izena`, `emaila` (unique), `telefonoa` (nullable), `odoo_id` (nullable, unique), timestamps.
- `Bezeroa` modeloa (`$fillable`).
- `GordeBezeroaRequest`: izena derrigorrezkoa, emaila baliozkoa eta bakarra.
- `BezeroaController@create` + `@store`:
  1. Balidatu.
  2. `OdooClient`-ekin `res.partner` sortu Odoon (**sinkronoan**: 3. astean Job batera berregituratuko da; ez aurreratu).
  3. `Bezeroa::updateOrCreate(['emaila' => ...], [..., 'odoo_id' => $id])`.
  4. Flash mezua eta birbideraketa.
- `OdooClient`: `create(string $model, array $vals): int` laguntzailea.
- Blade formularioa `@csrf` eta errore-mezuekin.

**Egiaztapena:** formularioak partner-a Odoon sortzen du (Odoo UI-an ikusi) eta lokalean `odoo_id`-arekin gordetzen du · email bikoiztua → balidazio-errorea · `docker compose stop odoo` + bidali → errore-mezu atsegina **eta lokalean ez da ezer gordetzen** (3. asteko "mina" erakusteko puntua).

### "Zuk zeuk" soluzioak
- `irakaslea/zuk-zeuk` adarra: S1–S3ko patroi berak `product.product`-ekin (curl, zerrenda, sortu).
- Ez nahastu `main`-eko tag-ekin.

---

## Etorkizuneko tag-ak (orain EZ eraiki; arkitekturak ahalbidetu behar ditu)

| Tag | Saioa | Edukia |
|---|---|---|
| s04 | 2. ast. 3h | PHPUnit oinarriak, `RefreshDatabase`, Factory-ak, `Http::fake`, `preventStrayRequests()` |
| s05 | 2. ast. 2h | S2–S3 fluxuen Feature testak |
| s06 | 2. ast. 2h | TDD: partner-a izenez bilatu |
| s07 | 3. ast. 3h | `SortuPartnerOdoon` Job-a, `bezeroak.egoera` (zain/baieztatua/huts), `queue` profila aktibatu |
| s08 | 3. ast. 2h | Ongietorri-emaila ilaran (Mailpit), `failed()` → email-a admin-ari |
| s09 | 3. ast. 2h | `failed_jobs`, `queue:retry`, `Queue::fake`, `Mail::fake` testak |
| s10 | 4. ast. 3h | `odoo:sinkronizatu-partnerrak` komandoa (inkrementala, `write_date`), scheduler, `scheduler` profila |
| s11 | 4. ast. 2h | Integrazio osoa, logak, `queue:restart` |

---

## Tag bakoitzaren amaieran sortu

`docs/laburpena-sNN.md` fitxategia, euskaraz:
- Saio honetan sortutako/aldatutako fitxategien zerrenda.
- Fitxategi bakoitzaren **kode osoa** (ez diff-a), live-coding-ean idazten den ordenan.
- Exekutatutako egiaztapen-komandoak eta haien irteera laburra.
- Aurkitutako arazoak edo ikasleek izan ditzaketen akatsak.

Fitxategi hauek gero klaseko gida (.docx) eta aurkezpena (.pptx) sortzeko erabiliko dira.
