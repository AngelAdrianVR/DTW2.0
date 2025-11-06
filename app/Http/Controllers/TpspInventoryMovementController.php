<?php

namespace App\Http\Controllers;

use App\Models\tpspInventoryMovement;
use App\Models\tpspProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TpspInventoryMovementController extends Controller
{
    /**
     * Muestra el historial de movimientos.
     * AHORA CON FILTROS.
     * Usado por: MovementsTab.vue
     */
    public function index(Request $request) // <--- IMPORTANTE: Inyectar Request
    {
        // Empezar la consulta base
        $query = TpspInventoryMovement::with('product');

        // --- FILTROS AÑADIDOS ---

        // 1. Filtrar por tipo de movimiento
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 2. Filtrar por fecha de inicio
        if ($request->filled('date_start')) {
            // Asegurarse que compare desde el inicio del día
            $query->where('created_at', '>=', $request->date_start . ' 00:00:00');
        }

        // 3. Filtrar por fecha de fin
        if ($request->filled('date_end')) {
            // Asegurarse que compare hasta el final del día
            $query->where('created_at', '<=', $request->date_end . ' 23:59:59');
        }

        // --- FIN DE FILTROS ---

        // Cargar el producto relacionado y ordenar por más reciente
        $movements = $query->orderBy('created_at', 'desc')
                          ->paginate(50); // Paginado es buena idea aquí

        return $movements;
    }

    /**
     * Almacena un nuevo movimiento y ACTUALIZA el stock del producto.
     * Usado por: MovementsTab.vue
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:tpsp_products,id',
            'type' => 'required|string', // Considera validación 'in:Compra,Venta,etc.'
            'quantity' => 'required|integer|not_in:0',
            
            // Validación condicional para los nuevos campos de precio
            'unit_price' => [
                Rule::requiredIf($request->type === 'Venta'),
                'nullable',
                'numeric',
                'min:0'
            ],
            'total_price' => [
                Rule::requiredIf($request->type === 'Venta'),
                'nullable',
                'numeric',
                'min:0'
            ],
            'notes' => 'nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);

        try {
            // Usar una transacción para asegurar la integridad de los datos
            // Si falla la actualización de stock, no se crea el movimiento.
            $movement = DB::transaction(function () use ($validatedData) {
                
                // 1. Crear el movimiento de inventario
                $movement = TpspInventoryMovement::create($validatedData);

                // 2. Actualizar el stock del producto
                $product = tpspProduct::find($validatedData['product_id']);
                
                // $product->stock += $validatedData['quantity'];
                // Usar increment/decrement es más seguro para concurrencia
                
                if ($validatedData['quantity'] > 0) {
                    $product->increment('stock', $validatedData['quantity']);
                } else {
                    $product->decrement('stock', abs($validatedData['quantity']));
                }

                // Opcional: asociar el movimiento con una referencia (ej. una orden)
                // $movement->reference()->associate($order)->save();

                return $movement;
            });

            return response()->json($movement, 201);

        } catch (\Exception $e) {
            // Si algo falla, la transacción hace rollback
            return response()->json(['message' => 'Error al registrar el movimiento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AÑADIDO: Muestra el historial PÚBLICO de ventas.
     * Usado por: PublicInventory.vue
     * * Este método es similar a index() pero:
     * 1. Es público.
     * 2. FUERZA el tipo a 'Venta'.
     * 3. NO DEVUELVE precios, solo campos seguros.
     */
    public function publicSalesHistory(Request $request)
    {
        // return 'hola';
        // Empezar la consulta base, forzando 'Venta'
        $query = TpspInventoryMovement::where('type', 'Venta');

        // --- Aplicar Filtros de Fecha ---

        // 1. Filtrar por fecha de inicio
        if ($request->filled('date_start')) {
            $query->where('created_at', '>=', $request->date_start . ' 00:00:00');
        }

        // 2. Filtrar por fecha de fin
        if ($request->filled('date_end')) {
            $query->where('created_at', '<=', $request->date_end . ' 23:59:59');
        }

        // --- FIN DE FILTROS ---

        // Cargar el producto relacionado y ordenar por más reciente
        $movements = $query
            ->with(['product' => function ($query) {
                // Cargar solo los campos necesarios del producto
                $query->select('id', 'name')->with('media');
            }])
            ->select('id', 'product_id', 'quantity', 'created_at') // <--- SOLO campos públicos
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return $movements;
    }
}
