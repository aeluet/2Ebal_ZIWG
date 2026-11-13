# S01 laburpena: Docker + Odoo + lehen deia

Tag-a: `s01-amaiera` (3h saioa).

## Saioaren ordena

1. Ingurunea martxan (`./bin/hasieratu.sh`) eta Odoo-ren `/doc` orria arakatu.
2. API gakoa eskuz sortu Odoo-ren interfazetik (README.md, "Odoo-ren API gakoa sortu").
3. Lehen deiak **curl** bidez, Laravel ukitu aurretik (`docs/curl-adibideak.md`).
4. Laravel: `config/services.php` → `OdooClient` → `tinker`-en probatu.

## Sortutako / aldatutako fitxategiak

| Fitxategia | Egoera |
|---|---|
| `docs/curl-adibideak.md` | berria |
| `config/services.php` | aldatua (`odoo` gakoa) |
| `app/Services/Odoo/OdooClient.php` | berria |
| `README.md` | aldatua (curl eta tinker estekak) |
| `Dockerfile` | aldatua (`ENV HOME=/tmp`, tinker-erako) |

## Kodea (live-coding ordenan)

### 1. `docs/curl-adibideak.md`

Ikus fitxategi osoa: [curl-adibideak.md](curl-adibideak.md). Laburpena:

```bash
export ODOO_API_KEY=itsatsi-hemen-gakoa

curl -s -X POST http://localhost:8069/json/2/res.partner/search_read \
  -H "Authorization: bearer $ODOO_API_KEY" \
  -H "Content-Type: application/json" \
  -H "X-Odoo-Database: antzokia" \
  -H "User-Agent: antzokia-laravel" \
  -d '{"domain": [], "fields": ["name", "email", "phone"], "limit": 5}'
```

### 2. `config/services.php` (gehitutako zatia)

```php
    // Odoo-ren JSON-2 API-a. Balioak .env fitxategitik datoz (gakoa inoiz ez kodean!).
    'odoo' => [
        'url' => env('ODOO_URL', 'http://odoo:8069'),
        'db' => env('ODOO_DB', 'antzokia'),
        'key' => env('ODOO_API_KEY'),
    ],
```

### 3. `app/Services/Odoo/OdooClient.php`

```php
<?php

namespace App\Services\Odoo;

use Illuminate\Support\Facades\Http;

class OdooClient
{
    // Odoo-ren JSON-2 API-ari dei bat egiten dio: POST {url}/json/2/{model}/{method}
    // $params: metodoaren parametroak (domain, fields, limit, ids...).
    public function call(string $model, string $method, array $params = []): array
    {
        return Http::baseUrl(config('services.odoo.url'))
            ->withToken(config('services.odoo.key'))
            ->withHeaders([
                'X-Odoo-Database' => config('services.odoo.db'),
                'User-Agent' => 'antzokia-laravel',
            ])
            ->timeout(10)
            ->post("/json/2/{$model}/{$method}", $params)
            ->throw()
            ->json();
    }
}
```

### 4. `tinker`-en probatu

```bash
docker compose exec app php artisan tinker
> app(\App\Services\Odoo\OdooClient::class)->call('res.partner', 'search_read', ['domain' => [], 'fields' => ['name', 'email'], 'limit' => 5]);
```

### 5. `Dockerfile` (gehitutako lerroak)

```dockerfile
# 1000 erabiltzaileak ez du etxe-karpetarik; tinker-ek (psysh) bertan idazten du.
ENV HOME=/tmp
```

## Egiaztapena

```
# curl, gaki zuzenarekin
search_read (limit 5)            → [{"id": 9, "name": "Acme Corporation", ...}, ...] 5 partner
search_read is_company = true    → Acme Corporation, Azure Interior, Gemini Furniture, LightsUp, Lumber Inc
search_count domain []           → 38
modelo okerra (res.partnerr)     → 404
eremu okerra ("izena")           → 500 "Invalid field 'izena' on 'res.partner'"

# curl, gaki okerrarekin
HTTP/1.1 401 UNAUTHORIZED
{"name": "werkzeug.exceptions.Unauthorized", "message": "Invalid apikey", "arguments": ["Invalid apikey", 401], ...}

# tinker
call('res.partner','search_read',['domain'=>[],'fields'=>['name','email'],'limit'=>3])
  → [{"id":9,"name":"Acme Corporation",...},{"id":36,"name":"Addison Olson",...},{"id":19,"name":"Douglas Fletcher",...}]
call('res.partner','search_read')                     → 38 partner (parametrorik gabe ere badabil)
domain [['name','ilike','acme']]                      → [{"id":9,"name":"Acme Corporation"}]
gaki okerrarekin                                      → Illuminate\Http\Client\RequestException, kodea 401

localhost:8000 → 200 (Dockerfile berreraiki ondoren)
```

## Arazoak eta ikasleen akats posibleak

- **`tinker`-ek huts egiten du** "Writing to directory /.config/psysh is not allowed" mezuarekin:
  1000 erabiltzaileak ez du `HOME`-rik. Konponbidea: `ENV HOME=/tmp` Dockerfile-n eta
  `docker compose up -d --build`. (`s00-hasiera`-n ere gertatzen da, baina han ez da tinker erabiltzen.)
- **`localhost` vs `odoo`**: curl ostalaritik → `http://localhost:8069`; Laravel edukiontzitik →
  `http://odoo:8069`. `.env`-n `ODOO_URL=http://localhost:8069` jarriz gero, `ConnectionException`.
- **Gakoa kopiatu gabe itxi** leihoa: Odoo-k ez du berriro erakusten; sortu beste bat.
- **`Authorization: bearer`**: `bearer` eta gakoaren artean hutsune bat. Laravel-en `withToken()`-ek
  `Bearer` bidaltzen du eta Odoo-k biak onartzen ditu.
- Odoo-n eremu hutsak **`false`** dira, ez `null` (adib. `"phone": false`).
- Errore-kodeak: gaki okerra 401, modelo okerra 404, eremu okerra 500. Hurrengo saioan (S02)
  salbuespen horiek harrapatu eta mezu atsegina erakutsiko dugu.
- `call()`-ek `array` itzultzen du; `search_count`-ek zenbaki bat itzultzen du, beraz `call()`-ekin
  `TypeError` ematen du. Oraingoz `search_read` bakarrik erabiltzen dugu Laravel-etik.
