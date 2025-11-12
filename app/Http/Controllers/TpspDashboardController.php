<?php

namespace App\Http\Controllers;

use App\Models\tpspInventoryMovement;
use App\Models\tpspProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TpspDashboardController extends Controller
{
    /**
     * Muestra la vista principal (Index) del módulo TPSP.
     * Esta función carga el componente principal de Vue (TpspIndex.vue).
     */
    public function index()
    {
        
        return Inertia::render('Tpsp/Index', [
            // Aquí puedes pasar props iniciales si lo necesitas,
            // aunque tus componentes ya cargan sus propios datos.
        ]);
    }

    public function publicInventory()
    {
        $products = tpspProduct::orderBy('name')
            // Cargar relación de medios
            ->with('media') 
            // Cargar relación de órdenes de producción, pero SOLO las activas
            ->with(['productionOrders' => function ($query) {
                // Filtra por los estados 'En Progreso' o 'Pendiente'
                $query->whereIn('status', ['En Progreso', 'Pendiente'])
                      // Selecciona solo los campos necesarios de las órdenes
                      ->select('id', 'product_id', 'quantity_requested', 'quantity_produced', 'status');
            }])
            // Selecciona los campos principales del producto
            ->select('id', 'name', 'stock', 'category')
            ->get();
        
        return Inertia::render('Tpsp/PublicInventory', [
            'products' => $products,
        ]);
    }

    /**
     * Obtiene datos financieros (ventas) para el gráfico del dashboard.
     */
    public function getFinancials(Request $request)
    {
        // Validar las fechas de entrada (ahora son opcionales)
        $request->validate([
            'date_start' => 'nullable|date_format:Y-m-d',
            'date_end' => 'nullable|date_format:Y-m-d|after_or_equal:date_start',
        ]);

        // Parsear fechas solo si existen
        $startDate = $request->date_start ? Carbon::parse($request->date_start) : null;
        $endDate = $request->date_end ? Carbon::parse($request->date_end) : null;

        // Consultar la base de datos
        $salesDataQuery = TpspInventoryMovement::where('type', 'Venta')
            ->select(
                // Agrupar por fecha (DATE(created_at))
                DB::raw('DATE(created_at) as date'),
                // Sumar el total_price
                DB::raw('SUM(total_price) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc'); // Ordenar por fecha

        // --- CORRECCIÓN ---
        // Aplicar el filtro de fecha solo si se proporcionaron las fechas
        // Usamos when() para encadenar la consulta condicionalmente
        $salesDataQuery->when($startDate, function ($query) use ($startDate, $endDate) {
            // Usar whereDate es más robusto para comparar solo la parte de la fecha
            $query->whereDate('created_at', '>=', $startDate->toDateString())
                  ->whereDate('created_at', '<=', $endDate->toDateString());
        });

        $salesData = $salesDataQuery->get();

        return response()->json($salesData);
    }
}
