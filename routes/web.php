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
