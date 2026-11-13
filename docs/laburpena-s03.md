# S03 laburpena: idatzi (nahita SINKRONOA)

Tag-a: `s03-amaiera` (2h saioa).

Helburua: formulario batetik bezero bat sortu, **lehenik Odoo-n** (`res.partner`) eta gero lokalean
(`bezeroak` taula), Odoo-ren id-a gordez. Deia **sinkronoa** da nahita: 3. astean Job batera
eramango dugu, eta saio honetako "mina" (Odoo erorita → ezer ez da gordetzen) da horren arrazoia.

## Sortutako / aldatutako fitxategiak

| Fitxategia | Egoera |
|---|---|
| `database/migrations/xxxx_xx_xx_xxxxxx_create_bezeroak_table.php` | berria (`make:migration`) |
| `app/Models/Bezeroa.php` | berria (`make:model`) |
| `app/Http/Requests/GordeBezeroaRequest.php` | berria (`make:request`) |
| `app/Services/Odoo/OdooClient.php` | aldatua (`create()`) |
| `app/Http/Controllers/BezeroaController.php` | berria (`make:controller`) |
| `routes/web.php` | aldatua (2 ibilbide) |
| `resources/views/bezeroak/create.blade.php` | berria |
| `resources/views/layouts/app.blade.php` | aldatua (flash mezua, esteka, estiloak) |

```bash
docker compose exec app php artisan make:migration create_bezeroak_table
docker compose exec app php artisan make:model Bezeroa
docker compose exec app php artisan make:request GordeBezeroaRequest
docker compose exec app php artisan make:controller BezeroaController
```

## Kodea (live-coding ordenan)

### 1. Migrazioa: `create_bezeroak_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bezeroak', function (Blueprint $table) {
            $table->id();
            $table->string('izena');
            $table->string('emaila')->unique();
            $table->string('telefonoa')->nullable();
            $table->unsignedBigInteger('odoo_id')->nullable()->unique(); // res.partner-en id-a
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bezeroak');
    }
};
```

```bash
docker compose exec app php artisan migrate
```

### 2. `app/Models/Bezeroa.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bezeroa extends Model
{
    // Laravel-ek "bezeroas" asmatuko luke; taularen izena guk esaten diogu.
    protected $table = 'bezeroak';

    protected $fillable = ['izena', 'emaila', 'telefonoa', 'odoo_id'];
}
```

### 3. `app/Http/Requests/GordeBezeroaRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GordeBezeroaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'izena' => ['required', 'string', 'max:255'],
            'emaila' => ['required', 'email', 'max:255', 'unique:bezeroak,emaila'],
            'telefonoa' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'izena.required' => 'Izena derrigorrezkoa da.',
            'emaila.required' => 'Emaila derrigorrezkoa da.',
            'emaila.email' => 'Emailak ez du formatu egokia.',
            'emaila.unique' => 'Email hori duen bezero bat badago jada.',
        ];
    }
}
```

### 4. `app/Services/Odoo/OdooClient.php` (gehitutako metodoa)

```php
    // Erregistro bat sortu eta bere id-a itzuli.
    // Odoo-ren create-ek erregistro-zerrenda bat jasotzen du (vals_list) eta id-en zerrenda itzultzen du.
    public function create(string $model, array $vals): int
    {
        $ids = $this->call($model, 'create', ['vals_list' => [$vals]]);

        return $ids[0];
    }
```

(Odoo 19-ko sinadura: `def create(self, vals_list: list[ValuesType]) -> Self`. JSON-2-k
erregistro-multzoa id-en zerrenda bihurtzen du.)

### 5. `app/Http/Controllers/BezeroaController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\GordeBezeroaRequest;
use App\Models\Bezeroa;
use App\Services\Odoo\OdooClient;
use App\Services\Odoo\OdooException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BezeroaController extends Controller
{
    // GET /bezeroak/sortu: formularioa.
    public function create(): View
    {
        return view('bezeroak.create');
    }

    // POST /bezeroak: bezeroa Odoo-n sortu eta lokalean gorde.
    public function store(GordeBezeroaRequest $request, OdooClient $odoo): RedirectResponse
    {
        // 1. Balidazioa: GordeBezeroaRequest-ek egiten du, metodoa exekutatu aurretik.
        $datuak = $request->validated();

        // 2. Partner-a Odoo-n sortu. SINKRONOA: erabiltzaileak Odoo-ren erantzunaren zain dago.
        try {
            $odooId = $odoo->create('res.partner', [
                'name' => $datuak['izena'],
                'email' => $datuak['emaila'],
                'phone' => $datuak['telefonoa'] ?? false,
            ]);
        } catch (OdooException $e) {
            report($e);

            return back()
                ->withInput()
                ->with('errorea', 'Ezin izan da bezeroa Odoo-n sortu. Saiatu berriro geroago.');
        }

        // 3. Lokalean gorde, Odoo-ren id-arekin.
        Bezeroa::updateOrCreate(
            ['emaila' => $datuak['emaila']],
            [
                'izena' => $datuak['izena'],
                'telefonoa' => $datuak['telefonoa'] ?? null,
                'odoo_id' => $odooId,
            ],
        );

        // 4. Flash mezua eta birbideraketa.
        return redirect()
            ->route('partnerrak.index')
            ->with('arrakasta', "{$datuak['izena']} bezeroa sortu da (Odoo id: {$odooId}).");
    }
}
```

### 6. `routes/web.php`

```php
<?php

use App\Http\Controllers\BezeroaController;
use App\Http\Controllers\OdooPartnerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/partnerrak', [OdooPartnerController::class, 'index'])->name('partnerrak.index');

Route::get('/bezeroak/sortu', [BezeroaController::class, 'create'])->name('bezeroak.create');
Route::post('/bezeroak', [BezeroaController::class, 'store'])->name('bezeroak.store');
```

### 7. `resources/views/bezeroak/create.blade.php`

```blade
@extends('layouts.app')

@section('izenburua', 'Bezero berria')

@section('edukia')
    @if (session('errorea'))
        <p class="errorea">{{ session('errorea') }}</p>
    @endif

    <form method="POST" action="{{ route('bezeroak.store') }}">
        @csrf

        <p>
            <label for="izena">Izena</label><br>
            <input type="text" id="izena" name="izena" value="{{ old('izena') }}">
            @error('izena') <br><span class="eremu-errorea">{{ $message }}</span> @enderror
        </p>

        <p>
            <label for="emaila">Emaila</label><br>
            <input type="email" id="emaila" name="emaila" value="{{ old('emaila') }}">
            @error('emaila') <br><span class="eremu-errorea">{{ $message }}</span> @enderror
        </p>

        <p>
            <label for="telefonoa">Telefonoa (aukerakoa)</label><br>
            <input type="text" id="telefonoa" name="telefonoa" value="{{ old('telefonoa') }}">
            @error('telefonoa') <br><span class="eremu-errorea">{{ $message }}</span> @enderror
        </p>

        <button type="submit">Gorde</button>
    </form>
@endsection
```

### 8. `resources/views/layouts/app.blade.php` (osorik, aldaketekin)

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
        .arrakasta { background: #e6f6e6; border: 1px solid #9c9; padding: .75rem; border-radius: 4px; }
        .eremu-errorea { color: #b00; font-size: .9rem; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Hasiera</a>
        <a href="{{ route('partnerrak.index') }}">Partner-ak</a>
        <a href="{{ route('bezeroak.create') }}">Bezero berria</a>
    </nav>

    <h1>@yield('izenburua')</h1>

    @if (session('arrakasta'))
        <p class="arrakasta">{{ session('arrakasta') }}</p>
    @endif

    @yield('edukia')
</body>
</html>
```

## Egiaztapena

Formularioa curl bidez bidalita (saio-cookie + `_token`), nabigatzaile batek bezala:

```
$ php artisan migrate
  2026_11_13_103434_create_bezeroak_table ....... DONE

1) "Proba Antzokia" / proba1@antzokia.eus / 943000111
   → /partnerrak, lokalean {"id":1, ..., "odoo_id":42}
2) "Proba Bi" / proba2@antzokia.eus / (hutsik)
   → /partnerrak: "Proba Bi bezeroa sortu da (Odoo id: 43)."  lokalean telefonoa=null
   Odoo-n: {"id": 43, "name": "Proba Bi", "email": "proba2@antzokia.eus", "phone": false}
3) email bikoiztua (proba1@antzokia.eus)
   → /bezeroak/sortu: "Email hori duen bezero bat badago jada."  (Odoo-n ez da ezer sortu)
4) izenik gabe + "ez-da-emaila"
   → "Izena derrigorrezkoa da." / "Emailak ez du formatu egokia." (old() balioak mantenduta)

$ docker compose stop odoo
5) "Proba Hiru" / proba3@antzokia.eus
   → /bezeroak/sortu: "Ezin izan da bezeroa Odoo-n sortu. Saiatu berriro geroago."
   → lokalean: 2 bezero, proba3: EZ   ← ezer ez da gorde
$ docker compose start odoo
/partnerrak → "Proba Antzokia" eta "Proba Bi" agertzen dira
```

## "Mina" (3. asteko sarrera)

Odoo erorita dagoenean bezeroa **galdu** egiten da: erabiltzaileak berriro bete behar du
formularioa, eta Odoo motel badago, orria 10 segundoz zain geratzen da (`timeout(10)`).
Galdetu ikasleei: nola gordeko genuke bezeroa *orain* eta Odoo-ra *gero* bidali?
→ 3. astean: `bezeroak.egoera` (zain/baieztatua/huts) + `SortuPartnerOdoon` Job-a.

## Arazoak eta ikasleen akats posibleak

- **Taularen izena**: `Bezeroa` modeloak `bezeroas` taula bilatzen du lehenetsiz →
  "Table 'antzokia.bezeroas' doesn't exist". Konponbidea: `protected $table = 'bezeroak';`.
- **`@csrf` ahaztea** → 419 "Page Expired".
- **`$fillable`-n eremu bat ahaztea** (adib. `odoo_id`) → ez da errorerik, baina zutabea `NULL` geratzen da.
- **`vals_list`**: Odoo-ren `create`-ek **zerrenda** bat espero du (`['vals_list' => [$vals]]`) eta
  id-en zerrenda itzultzen du (`[42]`), ez zenbaki bat → `$ids[0]`.
- **Telefono hutsa**: Laravel-ek string hutsak `null` bihurtzen ditu (`ConvertEmptyStringsToNull`).
  Odoo-ra `false` bidaltzen dugu (Odoo-ren "hutsa"), lokalean `null`.
- **Ordena garrantzitsua da**: lehenik Odoo, gero lokala. Alderantziz eginez gero, Odoo erorita
  dagoenean bezeroa lokalean geratuko litzateke `odoo_id`-rik gabe (eta email bikoiztuaren
  balidazioak ez luke berriro bidaltzen utziko).
- **`updateOrCreate`** gaur ez da beharrezkoa (`unique` balidazioak bikoizketa saihesten du),
  baina 3. astean Job-ak berriro saiatzean erabilgarria izango da.
- Probetan sortutako partner-ak Odoo-n geratzen dira (Odoo UI → Contacts → ezabatu, nahi izanez gero).
