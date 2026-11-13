# S02 laburpena: irakurri eta erakutsi

Tag-a: `s02-amaiera` (2h saioa).

Helburua: Odoo-ko partner-ak web-orri batean erakutsi (`GET /partnerrak`) eta, Odoo-k huts egiten
duenean, mezu atsegina erakutsi (ez Laravel-en stack trace-a).

## Sortutako / aldatutako fitxategiak

| Fitxategia | Egoera |
|---|---|
| `app/Services/Odoo/OdooException.php` | berria |
| `app/Services/Odoo/OdooClient.php` | aldatua (`try/catch` + `searchRead()`) |
| `app/Http/Controllers/OdooPartnerController.php` | berria |
| `routes/web.php` | aldatua (`/partnerrak`) |
| `resources/views/layouts/app.blade.php` | berria |
| `resources/views/partnerrak/index.blade.php` | berria |

## Kodea (live-coding ordenan)

### 1. `app/Services/Odoo/OdooException.php`

```php
<?php

namespace App\Services\Odoo;

use Exception;

// Odoo-rekin zerbait gaizki doanean jaurtitzen dugun salbuespena.
// Odoo-ren errore-mezua eta HTTP egoera gordetzen ditu (0 = ez dago konexiorik).
class OdooException extends Exception
{
    public function __construct(string $message, public int $egoera = 0)
    {
        parent::__construct($message, $egoera);
    }
}
```

### 2. `app/Services/Odoo/OdooClient.php`

```php
<?php

namespace App\Services\Odoo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OdooClient
{
    // Odoo-ren JSON-2 API-ari dei bat egiten dio: POST {url}/json/2/{model}/{method}
    // $params: metodoaren parametroak (domain, fields, limit, ids...).
    public function call(string $model, string $method, array $params = []): array
    {
        try {
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
        } catch (RequestException $e) {
            // Odoo-k erantzun du, baina errore batekin (401, 404, 500...).
            $mezua = $e->response->json('message') ?? $e->getMessage();
            throw new OdooException($mezua, $e->response->status());
        } catch (ConnectionException $e) {
            // Odoo-k ez du erantzun (geldituta, sare-errorea, timeout...).
            throw new OdooException('Ezin da Odoo-rekin konektatu: '.$e->getMessage());
        }
    }

    // Erregistroak bilatu eta irakurri, dei bakarrean.
    public function searchRead(string $model, array $domain, array $fields, int $limit = 50): array
    {
        return $this->call($model, 'search_read', [
            'domain' => $domain,
            'fields' => $fields,
            'limit' => $limit,
        ]);
    }
}
```

### 3. `app/Http/Controllers/OdooPartnerController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\Odoo\OdooClient;
use App\Services\Odoo\OdooException;
use Illuminate\View\View;

class OdooPartnerController extends Controller
{
    // GET /partnerrak: Odoo-ko partner-en zerrenda.
    public function index(OdooClient $odoo): View
    {
        try {
            $partnerrak = $odoo->searchRead('res.partner', [], ['name', 'email', 'phone']);
        } catch (OdooException $e) {
            report($e); // logean gorde, xehetasunak ikusi ahal izateko

            return view('partnerrak.index', [
                'partnerrak' => [],
                'errorea' => 'Ezin izan dira partner-ak Odoo-tik kargatu. Saiatu berriro geroago.',
            ]);
        }

        return view('partnerrak.index', ['partnerrak' => $partnerrak]);
    }
}
```

### 4. `routes/web.php`

```php
<?php

use App\Http\Controllers\OdooPartnerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/partnerrak', [OdooPartnerController::class, 'index'])->name('partnerrak.index');
```

### 5. `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('izenburua') · Bergarako Antzokia</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 960px; margin: 0 auto; padding: 1rem; color: #222; }
        nav a { margin-right: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .4rem; border-bottom: 1px solid #ddd; }
        .errorea { background: #fde8e8; border: 1px solid #e0a0a0; padding: .75rem; border-radius: 4px; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Hasiera</a>
        <a href="{{ route('partnerrak.index') }}">Partner-ak</a>
    </nav>

    <h1>@yield('izenburua')</h1>

    @yield('edukia')
</body>
</html>
```

### 6. `resources/views/partnerrak/index.blade.php`

```blade
@extends('layouts.app')

@section('izenburua', 'Odoo-ko partner-ak')

@section('edukia')
    @isset($errorea)
        <p class="errorea">{{ $errorea }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Izena</th>
                    <th>Emaila</th>
                    <th>Telefonoa</th>
                </tr>
            </thead>
            <tbody>
                {{-- Odoo-n eremu hutsak "false" dira, ez "null" --}}
                @forelse ($partnerrak as $partnerra)
                    <tr>
                        <td>{{ $partnerra['name'] }}</td>
                        <td>{{ $partnerra['email'] ?: '—' }}</td>
                        <td>{{ $partnerra['phone'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Ez dago partner-ik.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endisset
@endsection
```

## Egiaztapena

```
$ curl localhost:8000/partnerrak                      → HTTP 200, 39 <tr> (goiburua + 38 partner)
  <td>Acme Corporation</td><td>acme_corp@yourcompany.example.com</td><td>(603)-996-3829</td> ...

$ docker compose stop odoo
$ curl localhost:8000/partnerrak                      → HTTP 200, taularik gabe
  <p class="errorea">Ezin izan dira partner-ak Odoo-tik kargatu. Saiatu berriro geroago.</p>
  laravel.log: OdooException(code: 0): Ezin da Odoo-rekin konektatu: cURL error 6: Could not resolve host: odoo
$ docker compose start odoo                           → /partnerrak berriro 39 <tr>

# Gaki okerra (config(['services.odoo.key' => 'gaki-okerra']) + HTTP kernel-a tinker-en)
/partnerrak                                           → 200 | Ezin izan dira partner-ak Odoo-tik kargatu...
searchRead(...)                                       → OdooException | egoera=401 | Invalid apikey
call('res.partnerr', 'search_read')                   → OdooException | egoera=404 | the model 'res.partnerr' does not exist
searchRead('res.partner', [['is_company','=',true]], ['name'], 3) → 3 erregistro

$ php artisan route:list --path=partnerrak
  GET|HEAD  partnerrak ... partnerrak.index › OdooPartnerController@index
```

Gaki okerra klasean probatzeko: aldatu `.env`-ko `ODOO_API_KEY` eta freskatu orria
(`config:cache` erabili bada, `php artisan config:clear`).

## Arazoak eta ikasleen akats posibleak

- **`use` ahaztea**: `RequestException` eta `ConnectionException` `Illuminate\Http\Client`-ekoak dira.
  `use` gabe, `catch`-ak ez du ezer harrapatzen (PHP-k ez du errorerik ematen!) eta stack trace-a agertzen da.
- **`catch (Exception $e)` orokorra lehenengo** jarriz gero, beste `catch`-ak ez dira inoiz exekutatzen.
- **`$e->response->json('message')`**: Odoo-ren errore-JSONaren `message` eremua. Erantzuna JSON ez bada
  (adib. proxy baten HTML orria), `null` itzultzen du → `??` bidez Laravel-en mezua erabiltzen dugu.
- **Odoo geldituta → `ConnectionException`** ("Could not resolve host: odoo"), ez `RequestException`:
  ez dago HTTP erantzunik, beraz `egoera` 0 da.
- **Eremu hutsak `false`**: `?: '—'` erabili. `??` ez dabil, `false` ez delako `null`.
- **`report($e)`**: mezu atsegina erabiltzaileari, xehetasunak `storage/logs/laravel.log`-en.
  `catch` hutsa idatziz gero, errorea "isilik" galtzen da.
- `searchRead()`-ek 50 erregistro itzultzen ditu lehenetsiz (`$limit = 50`); demo datuetan 38 dira.
