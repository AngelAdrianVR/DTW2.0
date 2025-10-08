<?php

use App\Http\Controllers\Api\PomodoroController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\HostingClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
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
Route::get('/quotes/{quote}/print', [QuoteController::class, 'print'])->name('quotes.print')->middleware(['auth']);
Route::post('/quotes/{quote}/invoices', [QuoteController::class, 'storeInvoice'])->name('quotes.invoices.store')->middleware(['auth']);
Route::delete('/quotes/{quote}/invoices/{media}', [QuoteController::class, 'destroyInvoice'])->name('quotes.invoices.destroy')->middleware(['auth']);
// Cotizaciones desde la web
Route::post('/quote-request', [QuoteController::class, 'handleWebRequest'])->name('quote.web.request');


// Rutas de Proyectos --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::resource('projects', ProjectController::class)->middleware('auth');


// Rutas para Tareas --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::resource('tasks', TaskController::class)->except(['index', 'create','edit','show'])->middleware('auth');
Route::patch('/tasks/{task}/update-status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');


// Rutas de usuarios --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::resource('users', UserController::class)->middleware('auth');


// Rutas de hostings --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::resource('hosting-clients', HostingClientController::class)->middleware('auth');
Route::post('/hosting-clients/{hostingClient}/payments', [HostingClientController::class, 'storePayment'])->name('hosting-clients.payments.store');
Route::patch('/hosting-clients/{hostingClient}/status', [HostingClientController::class, 'updateStatus'])->name('hosting-clients.status.update');


// Rutas de pomodoro --------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------
Route::middleware('auth:sanctum')->prefix('pomodoro')->group(function () {
    Route::get('/settings', [PomodoroController::class, 'getSettings']);
    Route::post('/settings', [PomodoroController::class, 'saveSettings']);
    Route::post('/pause-tasks', [PomodoroController::class, 'pauseActiveTasks']);
    Route::post('/resume-tasks', [PomodoroController::class, 'resumePausedTasks']);
});


