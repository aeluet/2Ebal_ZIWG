# Odoo JSON-2 API-a curl bidez

Laravel ukitu aurretik, API-a "eskuz" probatuko dugu terminaletik.
Ordenagailutik (ostalaritik) Odoo `http://localhost:8069` helbidean dago.

## 0. Prestatu

Kopiatu zure API gakoa (ikus README.md, "Odoo-ren API gakoa sortu") eta gorde aldagai batean:

```bash
export ODOO_API_KEY=itsatsi-hemen-gakoa
```

## Deiaren egitura

```
POST http://localhost:8069/json/2/{modeloa}/{metodoa}
```

Goiburuak:

| Goiburua | Balioa |
|---|---|
| `Authorization` | `bearer <API gakoa>` |
| `Content-Type` | `application/json` |
| `X-Odoo-Database` | `antzokia` |
| `User-Agent` | `antzokia-laravel` |

Gorputza: JSON objektu bat, metodoaren parametroekin (`domain`, `fields`, `limit`...).
Parametroen izenak `/doc` orrian ikusten dira (http://localhost:8069/doc → `res.partner` → `search_read`).

## 1. Partner-ak irakurri (`search_read`)

```bash
curl -s -X POST http://localhost:8069/json/2/res.partner/search_read \
  -H "Authorization: bearer $ODOO_API_KEY" \
  -H "Content-Type: application/json" \
  -H "X-Odoo-Database: antzokia" \
  -H "User-Agent: antzokia-laravel" \
  -d '{"domain": [], "fields": ["name", "email", "phone"], "limit": 5}'
```

Erantzuna: partner-en zerrenda bat (JSON array-a):

```json
[{"id": 9, "name": "Acme Corporation", "email": "acme_corp@yourcompany.example.com", "phone": "(603)-996-3829"}, ...]
```

Kontuz: Odoo-n eremu huts batek `false` itzultzen du, ez `null`.

## 2. Iragazi (`domain`)

Domeinua baldintzen zerrenda bat da: `[eremua, eragilea, balioa]`.

```bash
curl -s -X POST http://localhost:8069/json/2/res.partner/search_read \
  -H "Authorization: bearer $ODOO_API_KEY" \
  -H "Content-Type: application/json" \
  -H "X-Odoo-Database: antzokia" \
  -H "User-Agent: antzokia-laravel" \
  -d '{"domain": [["is_company", "=", true]], "fields": ["name", "email"], "limit": 5}'
```

Beste adibide batzuk: `[["name", "ilike", "acme"]]`, `[["email", "!=", false]]`.

## 3. Zenbat dira? (`search_count`)

```bash
curl -s -X POST http://localhost:8069/json/2/res.partner/search_count \
  -H "Authorization: bearer $ODOO_API_KEY" \
  -H "Content-Type: application/json" \
  -H "X-Odoo-Database: antzokia" \
  -H "User-Agent: antzokia-laravel" \
  -d '{"domain": []}'
```

Erantzuna zenbaki bat da, ez array bat.

## 4. Erroreak

API gaki okerrarekin (`-i` erantzunaren goiburuak ikusteko):

```bash
curl -s -i -X POST http://localhost:8069/json/2/res.partner/search_read \
  -H "Authorization: bearer gaki-okerra" \
  -H "Content-Type: application/json" \
  -H "X-Odoo-Database: antzokia" \
  -H "User-Agent: antzokia-laravel" \
  -d '{"domain": [], "fields": ["name"], "limit": 1}'
```

Erantzuna: `HTTP/1.1 401 UNAUTHORIZED` eta errorearen JSONa (`name`, `message`, `arguments`, `context`, `debug`).

Probatu ere: existitzen ez den modelo bat (`res.partnerr`) edo eremu oker bat (`"fields": ["izena"]`).
Zein HTTP egoera itzultzen du bakoitzak?
