<?php

namespace App\Http\Controllers;

use App\Models\tpspInventoryMovement;
use App\Models\tpspProduct;
use App\Models\tpspProductionOrder; // AÑADIDO: Para calcular faltantes de órdenes
use App\Models\tpspKitComponent;    // AÑADIDO: Para calcular recetas
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

    /**
     * Muestra el Inventario Público y calcula los insumos requeridos
     * basándose en las órdenes de producción activas.
     */
    public function publicInventory()
    {
        // 1. Obtenemos los productos públicos con sus imágenes y las órdenes que están en curso
        $products = tpspProduct::where('is_public', true) // Filtramos solo los públicos
            ->orderBy('name')
            // Cargar relación de medios
            ->with('media') 
            // Cargar relación de órdenes de producción, pero SOLO las activas (Se usa para los Kits Terminados)
            ->with(['productionOrders' => function ($query) {
                $query->whereIn('status', ['En Progreso', 'Pendiente'])
                      ->select('id', 'product_id', 'quantity_requested', 'quantity_produced', 'status', 'order_number');
            }])
            ->get();
        
        // 2. Obtenemos las órdenes de producción pendientes (globales)
        $pendingOrders = tpspProductionOrder::whereNotIn('status', ['Completado', 'Cancelado'])->get();

        // 3. Obtenemos todas las recetas/componentes agrupados por el Kit al que pertenecen
        $kitComponents = tpspKitComponent::all()->groupBy('kit_product_id');

        // 4. Calculamos cuánto se requiere de cada producto en base a las recetas
        foreach ($products as $product) {
            $requiredQty = 0;
            $requiringOrders = [];

            foreach ($pendingOrders as $order) {
                $componentsOfKit = $kitComponents->get($order->product_id);
                
                if ($componentsOfKit) {
                    $component = $componentsOfKit->firstWhere('component_product_id', $product->id);
                    
                    if ($component) {
                        // Cuántos kits faltan por producir de esta orden
                        $missingToProduceKit = $order->quantity_requested - $order->quantity_produced;
                        
                        if ($missingToProduceKit > 0) {
                            // Multiplicamos lo que falta del kit por lo que requiere la receta
                            $qtyNeeded = $missingToProduceKit * $component->quantity_required;
                            $requiredQty += $qtyNeeded;
                            
                            // Guardamos qué orden lo está pidiendo para mostrarlo en Vue
                            $requiringOrders[] = [
                                'order_number' => $order->order_number,
                                'missing_qty' => $qtyNeeded
                            ];
                        }
                    }
                }
            }
            
            // Inyectamos las variables dinámicas al producto al vuelo
            $product->required_quantity = $requiredQty;
            $product->requiring_orders = $requiringOrders;
        }
        
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