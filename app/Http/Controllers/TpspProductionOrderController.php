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

        $order = TpspProductionOrder::create($validatedData);
        $order->load('product'); 

        return response()->json($order, 201);
    }

    /**
     * Actualiza los campos básicos de una orden de producción.
     */
    public function update(Request $request, $id)
    {
        $order = TpspProductionOrder::findOrFail($id);

        $validatedData = $request->validate([
            'quantity_requested' => 'required|integer|min:1',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // No permitir reducir la cantidad por debajo de lo ya producido
        if ($validatedData['quantity_requested'] < $order->quantity_produced) {
            return response()->json([
                'message' => 'No puedes reducir la cantidad solicitada por debajo de lo ya producido (' . $order->quantity_produced . ' unidades).'
            ], 422);
        }

        $order->update($validatedData);
        $order->load('product');

        return response()->json($order);
    }

    /**
     * Elimina una orden de producción.
     */
    public function destroy($id)
    {
        $order = TpspProductionOrder::findOrFail($id);
        
        if ($order->quantity_produced > 0 || $order->quantity_delivered > 0) {
            return response()->json(['message' => 'No se puede eliminar una orden que ya tiene producción o entregas registradas.'], 422);
        }
        
        $order->delete();
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
            
            $components = tpspKitComponent::where('kit_product_id', $kitProduct->id)
                                          ->with('componentProduct') 
                                          ->get();

            if ($kitProduct->is_kit && $components->isEmpty()) {
                throw new \Exception('Este producto está marcado como Kit pero no tiene componentes definidos.');
            }

            foreach ($components as $component) {
                $componentProduct = $component->componentProduct; 
                
                if (!$componentProduct) {
                    throw new \Exception("El componente ID {$component->component_product_id} (requerido por el Kit) no fue encontrado.");
                }

                $requiredQty = $component->quantity_required * $quantityToAdd;

                if ($componentProduct->stock < $requiredQty) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stock insuficiente para el componente: {$componentProduct->name}. Se necesitan {$requiredQty}, disponibles: {$componentProduct->stock}."
                    ]);
                }

                $componentProduct->decrement('stock', $requiredQty);

                tpspInventoryMovement::create([
                    'product_id' => $component->component_product_id,
                    'quantity' => -$requiredQty, 
                    'type' => 'Consumo_Produccion',
                    'reference_type' => tpspProductionOrder::class,
                    'reference_id' => $order->id,
                    'notes' => "Consumo para Kit: {$kitProduct->name}. Orden: {$order->order_number}",
                ]);
            }

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