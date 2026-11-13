<?php

use App\Http\Controllers\OdooPartnerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/partnerrak', [OdooPartnerController::class, 'index'])->name('partnerrak.index');
