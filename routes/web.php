<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\QuoteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ruta principal que muestra la página de inicio ---------------------------------------------
// --------------------------------------------------------------------------------------------
Route::get('/', [LandingController::class, 'index'])->name('landing.index');

// Nueva ruta para cambiar el idioma
Route::get('language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});


// Rutas de Clientes --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::resource('clients', ClientController::class)->middleware('auth');


// Rutas de pagos de clientes --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::resource('client-payments', ClientPaymentController::class)->middleware('auth');


// Rutas de Cotizaciones --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::resource('quotes', QuoteController::class)->middleware('auth');
Route::put('/quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.updateStatus')->middleware(['auth', 'verified']);
// Cotizaciones desde la web
Route::post('/quote-request', [QuoteController::class, 'handleWebRequest'])->name('quote.web.request');
