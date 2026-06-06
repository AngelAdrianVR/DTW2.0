<?php

namespace App\Http\Controllers;

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
     */
    public function index()
    {
        // Se agregó orderBy('created_at', 'desc') para mostrar la más nueva arriba
        // Y withSum() para calcular totales y abonos en tiempo real y mostrar "Cerrada / Pagada"
        $orders = TpspProductionOrder::with('product')
            ->withSum(['inventoryMovements as total_price_sum' => function ($query) {
                $query->where('type', 'Venta');
            }], 'total_price')
            ->withSum(['inventoryMovements as amount_paid_sum' => function ($query) {
                $query->where('type', 'Venta');
            }], 'amount_paid')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return $orders;
    }

    /**
     * Almacena una nueva orden de producción.
     * Descuenta los materiales del inventario al momento de crearla.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:tpsp_products,id',
            'quantity_requested' => 'required|integer|min:1',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $nextId = (TpspProductionOrder::max('id') ?? 0) + 1;
        $validatedData['order_number'] = 'TPSP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        $validatedData['status'] = 'Pendiente';

        $warnings = [];
        $kitProduct = tpspProduct::find($validatedData['product_id']);
        $quantityRequested = $validatedData['quantity_requested'];

        if ($kitProduct && $kitProduct->is_kit) {
            $components = tpspKitComponent::where('kit_product_id', $kitProduct->id)
                ->with('componentProduct')
                ->get();

            try {
                DB::beginTransaction();

                $order = TpspProductionOrder::create($validatedData);

                foreach ($components as $component) {
                    $componentProduct = $component->componentProduct;
                    if (!$componentProduct) continue;

                    $requiredQty = $component->quantity_required * $quantityRequested;

                    // Descontar del inventario (permite quedar negativo)
                    $componentProduct->decrement('stock', $requiredQty);
                    $componentProduct->refresh();

                    tpspInventoryMovement::create([
                        'product_id' => $component->component_product_id,
                        'quantity' => -$requiredQty,
                        'type' => 'Consumo_Produccion',
                        'reference_type' => TpspProductionOrder::class,
                        'reference_id' => $order->id,
                        'notes' => "Consumo inicial para Kit: {$kitProduct->name}. Orden: {$order->order_number} ({$quantityRequested} unid.)",
                    ]);

                    if ($componentProduct->stock < 0) {
                        $warnings[] = "⚠️ {$componentProduct->name}: stock en NEGATIVO ({$componentProduct->stock}) tras descontar {$requiredQty} para producir {$quantityRequested} unidad(es).";
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Error al crear la orden: ' . $e->getMessage()], 500);
            }
        } else {
            $order = TpspProductionOrder::create($validatedData);
        }

        $order->load('product');

        $response = $order->toArray();
        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return response()->json($response, 201);
    }

    /**
     * Actualiza los campos básicos de una orden de producción.
     * Ajusta el inventario de componentes según el delta de cantidad.
     */
    public function update(Request $request, $id)
    {
        $order = TpspProductionOrder::findOrFail($id);
        $oldQuantity = $order->quantity_requested;

        $validatedData = $request->validate([
            'quantity_requested' => 'required|integer|min:1',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $newQuantity = $validatedData['quantity_requested'];
        $delta = $newQuantity - $oldQuantity;

        // No permitir reducir la cantidad por debajo de lo ya producido
        if ($newQuantity < $order->quantity_produced) {
            return response()->json([
                'message' => 'No puedes reducir la cantidad solicitada por debajo de lo ya producido (' . $order->quantity_produced . ' unidades).'
            ], 422);
        }

        $warnings = [];
        $kitProduct = tpspProduct::find($order->product_id);
        $components = collect();

        if ($kitProduct && $kitProduct->is_kit) {
            $components = tpspKitComponent::where('kit_product_id', $kitProduct->id)
                ->with('componentProduct')
                ->get();

            // Solo ajustar inventario si hay un delta real
            if ($delta != 0) {
                try {
                    DB::beginTransaction();

                    foreach ($components as $component) {
                        $componentProduct = $component->componentProduct;
                        if (!$componentProduct) continue;

                        $adjustQty = $component->quantity_required * abs($delta);

                        if ($delta > 0) {
                            // === AUMENTO: descontar materiales del inventario ===
                            $componentProduct->decrement('stock', $adjustQty);
                            // Refrescar para obtener el stock real después del decremento
                            $componentProduct->refresh();

                            tpspInventoryMovement::create([
                                'product_id' => $component->component_product_id,
                                'quantity' => -$adjustQty,
                                'type' => 'Consumo_Produccion',
                                'reference_type' => TpspProductionOrder::class,
                                'reference_id' => $order->id,
                                'notes' => "Consumo por ampliación de orden: {$kitProduct->name}. Orden: {$order->order_number} (+{$delta} unid.)",
                            ]);

                            if ($componentProduct->stock < 0) {
                                $warnings[] = "⚠️ {$componentProduct->name}: stock en NEGATIVO ({$componentProduct->stock}) tras descontar {$adjustQty} para cubrir el aumento de {$delta} unidad(es).";
                            }
                        } else {
                            // === DISMINUCIÓN: regresar materiales al inventario ===
                            $componentProduct->increment('stock', $adjustQty);
                            $componentProduct->refresh();

                            tpspInventoryMovement::create([
                                'product_id' => $component->component_product_id,
                                'quantity' => $adjustQty,
                                'type' => 'Ajuste',
                                'reference_type' => TpspProductionOrder::class,
                                'reference_id' => $order->id,
                                'notes' => "Devolución por reducción de orden: {$kitProduct->name}. Orden: {$order->order_number} ({$delta} unid.)",
                            ]);
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['message' => 'Error al ajustar inventario: ' . $e->getMessage()], 500);
                }
            }

            // === SIEMPRE: Calcular proyección de inventario para lo que falta producir ===
            $remainingToProduce = $newQuantity - $order->quantity_produced;

            foreach ($components as $component) {
                $componentProduct = $component->componentProduct;
                if (!$componentProduct) continue;

                $neededTotal = $component->quantity_required * $remainingToProduce;
                // Re-leer el stock fresco
                $freshProduct = tpspProduct::find($componentProduct->id);
                $futureStock = $freshProduct ? ($freshProduct->stock - $neededTotal) : ($componentProduct->stock - $neededTotal);

                if ($futureStock < 0) {
                    // Evitar duplicar la advertencia si ya se agregó arriba
                    $alreadyWarned = false;
                    foreach ($warnings as $w) {
                        if (str_contains($w, $componentProduct->name) && str_contains($w, 'negativo')) {
                            $alreadyWarned = true;
                            break;
                        }
                    }
                    if (!$alreadyWarned) {
                        $warnings[] = "⚠️ {$componentProduct->name}: stock actual ({$freshProduct?->stock}) insuficiente. Faltarían " . abs($futureStock) . " para completar las {$remainingToProduce} unidades restantes.";
                    }
                }
            }
        }

        $order->update($validatedData);
        $order->load('product');

        $response = $order->toArray();
        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return response()->json($response);
    }

    /**
     * Elimina una orden de producción. Devuelve los materiales al inventario
     * si no ha iniciado producción.
     */
    public function destroy($id)
    {
        $order = TpspProductionOrder::findOrFail($id);
        
        if ($order->quantity_produced > 0 || $order->quantity_delivered > 0) {
            return response()->json(['message' => 'No se puede eliminar una orden que ya tiene producción o entregas registradas.'], 422);
        }

        try {
            DB::beginTransaction();

            // Devolver materiales al inventario si es un kit
            $kitProduct = tpspProduct::find($order->product_id);
            if ($kitProduct && $kitProduct->is_kit) {
                $components = tpspKitComponent::where('kit_product_id', $kitProduct->id)
                    ->with('componentProduct')
                    ->get();

                foreach ($components as $component) {
                    $componentProduct = $component->componentProduct;
                    if (!$componentProduct) continue;

                    $returnQty = $component->quantity_required * $order->quantity_requested;

                    $componentProduct->increment('stock', $returnQty);

                    tpspInventoryMovement::create([
                        'product_id' => $component->component_product_id,
                        'quantity' => $returnQty,
                        'type' => 'Ajuste',
                        'reference_type' => TpspProductionOrder::class,
                        'reference_id' => $order->id,
                        'notes' => "Devolución por cancelación de orden: {$kitProduct->name}. Orden: {$order->order_number}.",
                    ]);
                }
            }

            $order->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar la orden: ' . $e->getMessage()], 500);
        }

        return response()->noContent();
    }

    /**
     * Devuelve el historial de entregas de una orden.
     */
    public function deliveries($id)
    {
        $order = TpspProductionOrder::findOrFail($id);
        
        $deliveries = tpspInventoryMovement::where('reference_type', TpspProductionOrder::class)
            ->where('reference_id', $order->id)
            ->where('type', 'Venta')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($deliveries);
    }

    /**
     * Novedad: Registra o actualiza el pago de una entrega en específico.
     * Recuerda crear la ruta en tu api.php para apuntar a esta función.
     */
    public function updateDeliveryPayment(Request $request, $deliveryId)
    {
        $validatedData = $request->validate([
            'amount_paid' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        $movement = tpspInventoryMovement::findOrFail($deliveryId);
        
        $movement->update([
            'amount_paid' => $validatedData['amount_paid'],
            'payment_date' => $validatedData['payment_date'],
        ]);

        return response()->json($movement);
    }

    /**
     * Actualiza el estado de la orden.
     */
    public function updateStatus(Request $request, $id)
    {
        $order = TpspProductionOrder::findOrFail($id);
        
        $validatedData = $request->validate([
            'status' => 'required|string|in:En Progreso,Cancelado',
        ]);

        if (in_array($order->status, ['Completado', 'Cancelado'])) {
             return response()->json(['message' => 'Esta orden ya no puede cambiar de estado.'], 422);
        }

        $order->update(['status' => $validatedData['status']]);
        
        return response()->json($order->load('product'));
    }

    /**
     * Agrega progreso a una orden.
     * Los materiales ya fueron descontados en store()/update(), aquí solo se
     * registra el avance y se incrementa el stock del producto kit terminado.
     */
    public function addProgress(Request $request, $id)
    {
        $order = TpspProductionOrder::findOrFail($id);
        
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $quantityToAdd = $validated['quantity'];
        
        if (in_array($order->status, ['Completado', 'Cancelado'])) {
            throw ValidationException::withMessages(['quantity' => 'No se puede agregar progreso a una orden completada o cancelada.']);
        }
        
        $newTotalProduced = $order->quantity_produced + $quantityToAdd;
        
        if ($newTotalProduced > $order->quantity_requested) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad producida no puede exceder la cantidad solicitada.']);
        }

        try {
            DB::beginTransaction();

            $kitProduct = tpspProduct::find($order->product_id);

            // Los materiales ya fueron descontados del inventario en store()/update().
            // Aquí solo registramos la entrada de producción y subimos el stock del kit.

            $order->quantity_produced = $newTotalProduced;
            
            if ($order->status == 'Pendiente') {
                $order->status = 'En Progreso';
            }
            $order->save();

            tpspInventoryMovement::create([
                'product_id' => $order->product_id,
                'quantity' => $quantityToAdd, 
                'type' => 'Entrada_Produccion',
                'reference_type' => tpspProductionOrder::class,
                'reference_id' => $order->id,
                'notes' => 'Entrada por producción. Orden: ' . $order->order_number,
            ]);

            if ($kitProduct) {
                $kitProduct->increment('stock', $quantityToAdd);
            } else {
                throw new \Exception('No se encontró el producto asociado.');
            }

            DB::commit();

            return response()->json($order->load('product'));

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e; 
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al procesar el progreso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Registra la entrega (Venta) de una orden y la marca como Completada.
     */
    public function deliverOrder(Request $request, $id)
    {
        $order = TpspProductionOrder::findOrFail($id);
        
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'delivery_date' => 'required|date',
            'unit_price' => 'required|numeric|min:0'
        ]);

        if (in_array($order->status, ['Completado', 'Cancelado'])) {
            return response()->json(['message' => 'Esta orden ya fue completada o cancelada.'], 422);
        }

        $quantityToDeliver = $validated['quantity'];
        $availableToDeliver = $order->quantity_produced - ($order->quantity_delivered ?? 0);

        if ($quantityToDeliver > $availableToDeliver) {
            return response()->json(['message' => 'No tienes suficiente producción disponible para entregar esa cantidad.'], 422);
        }

        try {
            DB::beginTransaction();

            $totalPrice = $quantityToDeliver * $validated['unit_price'];

            tpspInventoryMovement::create([
                'product_id' => $order->product_id,
                'quantity' => -$quantityToDeliver, 
                'type' => 'Venta',
                'unit_price' => $validated['unit_price'],
                'total_price' => $totalPrice,
                // Inicializamos como no pagado por defecto
                'amount_paid' => 0, 
                'reference_type' => tpspProductionOrder::class,
                'reference_id' => $order->id,
                'notes' => 'Entrega de ' . $quantityToDeliver . ' unidades. Orden: ' . $order->order_number,
                'created_at' => $validated['delivery_date'],
            ]);
            
            $product = tpspProduct::find($order->product_id);
            if ($product) {
                $product->decrement('stock', $quantityToDeliver);
            }

            $order->quantity_delivered = ($order->quantity_delivered ?? 0) + $quantityToDeliver;
            
            if ($order->quantity_delivered >= $order->quantity_requested) {
                $order->status = 'Completado';
            }

            $order->save();

            DB::commit();
            
            return response()->json($order->load('product'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al procesar la entrega: ' . $e->getMessage()], 500);
        }
    }
}