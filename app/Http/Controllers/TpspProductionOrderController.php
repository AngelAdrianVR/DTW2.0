<?php

namespace App\Http\Controllers;

// Casing no estándar, pero seguimos el archivo original
use App\Models\tpspProductionOrder; 
use App\Models\tpspProduct;
use App\Models\tpspInventoryMovement;
use App\Models\tpspKitComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TpspProductionOrderController extends Controller
{
    /**
     * Muestra la lista de órdenes de producción.
     * Usado por: ProductionOrdersTab.vue
     */
    public function index()
    {
        // Cargar la relación 'product' para mostrar el nombre en la tabla
        $orders = TpspProductionOrder::with('product')
            ->orderBy('due_date', 'desc') // Mantenemos el orden original
            ->get();
            
        return $orders;
    }

    /**
     * Almacena una nueva orden de producción.
     * Usado por: TpspIndex.vue (Modal)
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:tpsp_products,id',
            'quantity_requested' => 'required|integer|min:1',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Lógica para generar 'order_number' (ej. TPSP-0001)
        $nextId = (TpspProductionOrder::max('id') ?? 0) + 1;
        $validatedData['order_number'] = 'TPSP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        $validatedData['status'] = 'Pendiente'; // Estado inicial

        $order = TpspProductionOrder::create($validatedData);

        // Cargamos el producto para retornarlo completo
        $order->load('product'); 

        return response()->json($order, 201);
    }

    // ... (Aquí irían los métodos show, update, destroy para el CRUD completo) ...

    /**
     * Actualiza el estado de una orden.
     * Ruta: PATCH /tpsp/production-orders/{order}/status
     */
    public function updateStatus(Request $request, TpspProductionOrder $order)
    {
        // Validamos solo los estados permitidos por el dropdown
        $validatedData = $request->validate([
            'status' => 'required|string|in:En Progreso,Cancelado',
        ]);

        // No permitir cambiar si ya está completado o cancelado
        if (in_array($order->status, ['Completado', 'Cancelado'])) {
             return response()->json(['message' => 'Esta orden ya no puede cambiar de estado.'], 422);
        }

        $order->update(['status' => $validatedData['status']]);
        
        // Retornamos la orden actualizada con su producto
        return response()->json($order->load('product'));
    }

    /**
     * Agrega progreso (cantidad producida) a una orden.
     * NUEVA LÓGICA: Ahora también descuenta componentes.
     * Ruta: POST /tpsp/production-orders/{order}/add-progress
     */
    public function addProgress(Request $request, TpspProductionOrder $order)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $quantityToAdd = $validated['quantity'];
        
        // --- Validación de Lógica de Negocio ---
        if (in_array($order->status, ['Completado', 'Cancelado'])) {
            throw ValidationException::withMessages(['quantity' => 'No se puede agregar progreso a una orden completada o cancelada.']);
        }
        
        $newTotalProduced = $order->quantity_produced + $quantityToAdd;
        
        if ($newTotalProduced > $order->quantity_requested) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad producida no puede exceder la cantidad solicitada.']);
        }

        // --- Transacción de Base de Datos ---
        try {
            DB::beginTransaction();

            // --- INICIO DE NUEVA LÓGICA: DESCONTAR COMPONENTES ---

            // 1. Obtener el producto (Kit) y sus componentes
            $kitProduct = tpspProduct::find($order->product_id);
            
            // Asumimos que la relación en tpspProduct se llama 'kitComponents'
            // y que el modelo tpspKitComponent tiene una relación 'componentProduct' al insumo
            $components = tpspKitComponent::where('kit_product_id', $kitProduct->id)
                                          ->with('componentProduct') 
                                          ->get();

            if ($kitProduct->is_kit && $components->isEmpty()) {
                throw new \Exception('Este producto está marcado como Kit pero no tiene componentes definidos.');
            }

            foreach ($components as $component) {
                // El producto 'Insumo' o 'Material'
                $componentProduct = $component->componentProduct; 
                
                if (!$componentProduct) {
                    throw new \Exception("El componente ID {$component->component_product_id} (requerido por el Kit) no fue encontrado.");
                }

                $requiredQty = $component->quantity_required * $quantityToAdd;

                // 2. Validar stock del componente
                if ($componentProduct->stock < $requiredQty) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stock insuficiente para el componente: {$componentProduct->name}. Se necesitan {$requiredQty}, disponibles: {$componentProduct->stock}."
                    ]);
                }

                // 3. Descontar stock del componente
                $componentProduct->decrement('stock', $requiredQty);

                // 4. Crear movimiento de 'Consumo_Produccion' para el componente
                tpspInventoryMovement::create([
                    'product_id' => $component->component_product_id,
                    'quantity' => -$requiredQty, // Negativo para salida/consumo
                    'type' => 'Consumo_Produccion',
                    'reference_type' => tpspProductionOrder::class,
                    'reference_id' => $order->id,
                    'notes' => "Consumo para Kit: {$kitProduct->name}. Orden: {$order->order_number}",
                ]);
            }
            // --- FIN DE NUEVA LÓGICA ---


            // 5. Actualizar la orden de producción
            $order->quantity_produced = $newTotalProduced;
            
            // 2. Si estaba 'Pendiente', mover a 'En Progreso'
            if ($order->status == 'Pendiente') {
                $order->status = 'En Progreso';
            }
            $order->save();

            // 6. Crear el movimiento de inventario (Entrada de producto terminado)
            tpspInventoryMovement::create([
                'product_id' => $order->product_id,
                'quantity' => $quantityToAdd, // Positivo para entrada
                'type' => 'Entrada_Produccion',
                'reference_type' => tpspProductionOrder::class,
                'reference_id' => $order->id,
                'notes' => 'Entrada por producción. Orden: ' . $order->order_number,
            ]);

            // 7. Actualizar el stock del Kit (Producto Terminado)
            // (El $kitProduct ya lo teníamos cargado arriba)
            if ($kitProduct) {
                $kitProduct->increment('stock', $quantityToAdd);
            } else {
                // Esto no debería pasar si la BDD está bien
                throw new \Exception('No se encontró el producto asociado.');
            }

            DB::commit();

            // Retornamos la orden actualizada
            return response()->json($order->load('product'));

        } catch (ValidationException $e) {
            // Si la validación (ej. stock) falla, revertir
            DB::rollBack();
            throw $e; // Re-lanzar la excepción de validación para que Vue la reciba
        } catch (\Exception $e) {
            DB::rollBack();
            // Retornar un error 500
            return response()->json(['message' => 'Error al procesar el progreso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Registra la entrega (Venta) de una orden y la marca como Completada.
     * Ruta: POST /tpsp/production-orders/{order}/deliver
     */
    public function deliverOrder(Request $request, TpspProductionOrder $order)
    {
        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'unit_price' => 'required|numeric|min:0'
        ]);

        // --- Validación de Lógica de Negocio ---
        if (in_array($order->status, ['Completado', 'Cancelado'])) {
            throw ValidationException::withMessages(['general' => 'Esta orden ya fue completada o cancelada.']);
        }

        $quantityToDeliver = $order->quantity_produced;

        if ($quantityToDeliver <= 0) {
             throw ValidationException::withMessages(['general' => 'No hay cantidad producida para entregar.']);
        }

        // --- Transacción de Base de Datos ---
        try {
            DB::beginTransaction();

            $totalPrice = $quantityToDeliver * $validated['unit_price'];

            // 1. Crear el movimiento de inventario (Venta de producto terminado)
            // Nota: El stock ya fue incrementado por 'addProgress'. Esto lo descuenta.
            tpspInventoryMovement::create([
                'product_id' => $order->product_id,
                'quantity' => -$quantityToDeliver, // Negativo para salida/venta
                'type' => 'Venta',
                'unit_price' => $validated['unit_price'],
                'total_price' => $totalPrice,
                'reference_type' => tpspProductionOrder::class,
                'reference_id' => $order->id,
                'notes' => 'Venta por entrega. Orden: ' . $order->order_number . '. Fecha Entrega: ' . $validated['delivery_date'],
                'created_at' => $validated['delivery_date'], // Fecha de entrega
            ]);
            
            // 2. Actualizar el stock del Kit (Producto Terminado)
            $product = tpspProduct::find($order->product_id);
            if ($product) {
                // Validar que haya stock suficiente (aunque 'quantity_produced' debería ser la fuente de verdad)
                if ($product->stock < $quantityToDeliver) {
                     throw new \Exception('Stock insuficiente. El stock ('.$product->stock.') es menor que la cantidad producida ('.$quantityToDeliver.'). Revise movimientos.');
                }
                $product->decrement('stock', $quantityToDeliver);
            } else {
                throw new \Exception('No se encontró el producto asociado.');
            }
            
            // 3. Actualizar la orden a 'Completado'
            $order->status = 'Completado';
            $order->save();

            DB::commit();
            
            // Retornamos la orden actualizada
            return response()->json($order->load('product'));

        } catch (\Exception $e) {
            DB::rollBack();
            // Retornar un error 500
            return response()->json(['message' => 'Error al procesar la entrega: ' . $e->getMessage()], 500);
        }
    }
}