<?php

use App\Http\Controllers\PomodoroController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HostingClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TpspDashboardController;
use App\Http\Controllers\TpspInventoryMovementController;
use App\Http\Controllers\TpspKitComponentController;
use App\Http\Controllers\TpspProductController;
use App\Http\Controllers\TpspProductionOrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebContentController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ruta principal que muestra la página de inicio ---------------------------------------------
// --------------------------------------------------------------------------------------------
Route::get('/', [LandingController::class, 'index'])->name('landing.index');
Route::get('/landing-projects/{project}', [LandingController::class, 'showProject'])->name('landing-projects.show');


// Rutas para mostrar los documentos legales
Route::get('/terms-of-service', [LegalController::class, 'showTerms'])->name('terms.show');
Route::get('/privacy-policy', [LegalController::class, 'showPrivacy'])->name('privacy.show');


// Nueva ruta para cambiar el idioma
Route::get('language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');


// Rutas del Dashboard --------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------------------------
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::get('/dashboard/performance/{user}', [DashboardController::class, 'getWeeklyPerformance'])->middleware('auth')->name('dashboard.performance');
Route::get('/dashboard/financials', [DashboardController::class, 'getFinancialsByYear'])->name('dashboard.financials.by-year');


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
    Route::post('/log-session', [PomodoroController::class, 'logSession']);
});


// Asegúrate de proteger estas rutas con tu middleware de autenticación (ej. 'auth', 'admin')
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/web-contents', [WebContentController::class, 'index'])->name('webcontents.index');
    Route::post('/web-contents', [WebContentController::class, 'store'])->name('webcontents.store');
    Route::put('/web-contents/{webContent}', [WebContentController::class, 'update'])->name('webcontents.update');
    Route::post('/web-contents/{webContent}/update-image', [WebContentController::class, 'updateImage'])->name('webcontents.updateImage');
    Route::delete('/web-contents/{webContent}', [WebContentController::class, 'destroy'])->name('webcontents.destroy');
    Route::delete('/media/{media}', [WebContentController::class, 'destroyMedia'])->name('media.destroy');
});


// --- INICIO: Rutas del Módulo de Producción (TPSP) ---
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->prefix('tpsp')->name('tpsp.')->group(function () {

    // --- RUTA NUEVA PARA MOSTRAR LA VISTA INDEX ---
    // Esta es la ruta que carga tu TpspIndex.vue
    Route::get('/', [TpspDashboardController::class, 'index'])->name('index');
    
    // CRUD de Productos (Materiales y Kits)
    // /produccion/products
    Route::resource('products', TpspProductController::class);

    // Rutas para administrar los componentes de un Kit (Producto anidado)
    // /produccion/products/{product}/components/...
    Route::resource('products.components', TpspKitComponentController::class)
        ->shallow() // Optimiza rutas, ej: /produccion/components/{component}/edit
        ->except(['show']); // Rara vez se necesita una vista "show" para un componente

    // CRUD de Órdenes de Producción (Tarjetas)
    // /produccion/production-orders
    Route::resource('production-orders', TpspProductionOrderController::class);
    
    // Ruta para actualizar el estado de una orden
    Route::patch('production-orders/{order}/status', [TpspProductionOrderController::class, 'updateStatus'])
         ->name('production-orders.updateStatus');
        
    // Ruta para agregar progreso (Entrada_Produccion)
    Route::post('production-orders/{order}/add-progress', [TpspProductionOrderController::class, 'addProgress'])
        ->name('production-orders.addProgress');

    // Ruta para entregar (Venta y Completar)
    Route::post('production-orders/{order}/deliver', [TpspProductionOrderController::class, 'deliverOrder'])
        ->name('production-orders.deliver');

    // Log de Movimientos de Inventario (Solo ver y crear ajustes)
    // /produccion/inventory-movements
    Route::resource('inventory-movements', TpspInventoryMovementController::class)
         ->only(['index', 'create', 'store', 'show']);

    // --- NUEVA RUTA PARA DATOS FINANCIEROS ---
    Route::get('/financials', [TpspDashboardController::class, 'getFinancials'])
         ->name('financials');

});

// --- AÑADIDO: Nuevas rutas públicas ---
// Ruta para la página pública principal de inventario
Route::get('/public/inventario', [TpspDashboardController::class, 'publicInventory'])
     ->name('tpsp.public.inventory');

// Ruta para que el componente Vue filtre y cargue los movimientos de venta
Route::get('/public-sales-movements', [TpspInventoryMovementController::class, 'publicSalesHistory'])
     ->name('tpsp.public.sales-history');
// --- FIN: Nuevas rutas públicas ---

// // Historial público de órdenes de producción terminadas
// Route::get('/produccion/historial', [TpspProductionOrderController::class, 'publicHistory'])
//      ->name('produccion.public-history');
// // --- FIN: Rutas del Módulo de Producción (TPSP) ---


