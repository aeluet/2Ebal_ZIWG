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
