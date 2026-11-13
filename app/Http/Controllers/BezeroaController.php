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
