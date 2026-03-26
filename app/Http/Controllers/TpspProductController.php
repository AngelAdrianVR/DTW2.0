<?php

namespace App\Http\Controllers;

use App\Models\tpspProduct;
use App\Models\tpspInventoryMovement; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB; 
use Illuminate\Validation\Rule;

class TpspProductController extends Controller
{
    
    /**
     * Muestra una lista de los productos.
     */
    public function index(Request $request)
    {
        $query = TpspProduct::query();

        if ($request->boolean('is_kit')) {
            $query->where('is_kit', true);
        }

        $products = $query->orderBy('name')->get();
        
        $products->each(function ($product) {
            $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null; 
        });

        return $products;
    }

    /**
     * Almacena un nuevo producto.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:tpsp_products,sku',
            'category' => ['required', 'string', Rule::in(['Material', 'Insumo', 'Empaque', 'Producto Terminado'])],
            'unit_of_measure' => ['required', 'string', Rule::in(['Pieza', 'Mililitro', 'Gramo', 'Kit', 'Kilogramo','Metro','Rollo','Litro'])],
            'stock' => 'required|numeric',
            'is_kit' => 'required|boolean',
            'is_public' => 'boolean', // NUEVO: Validación de visibilidad pública
        ]);

        // Si no se envía is_public, forzamos a true por defecto
        if (!isset($validatedData['is_public'])) {
            $validatedData['is_public'] = true;
        }

        $product = TpspProduct::create($validatedData);

        if ($request->hasFile('image')) {
            $product->addMediaFromRequest('image')->toMediaCollection('products');
        }

        $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null;

        return response()->json($product, 201);
    }

    public function show(TpspProduct $product)
    {
        $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null;
        return $product;
    }

    public function update(Request $request, TpspProduct $product)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['nullable', 'string', Rule::unique('tpsp_products', 'sku')->ignore($product->id)],
            'category' => ['required', 'string', Rule::in(['Material', 'Insumo', 'Empaque', 'Producto Terminado'])],
            'unit_of_measure' => ['required', 'string', Rule::in(['Pieza', 'Mililitro', 'Gramo', 'Kit', 'Kilogramo','Metro','Rollo','Litro'])],
            'stock' => 'required|numeric',
            'is_kit' => 'required|boolean',
            'is_public' => 'boolean', // NUEVO: Validación de visibilidad pública
        ]);
        
        // Prevención en caso de que is_public venga nulo por el frontend
        if (!isset($validatedData['is_public'])) {
            $validatedData['is_public'] = false;
        }

        $product->update($validatedData);

        if ($request->hasFile('image')) {
            $product->clearMediaCollection('products');
            $product->addMediaFromRequest('image')->toMediaCollection('products');
        }
        
        $product->load('media'); 
        $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null;

        return response()->json($product);
    }

    public function destroy(TpspProduct $product)
    {
        $product->loadCount(['inventoryMovements', 'productionOrders']);

        if ($product->inventory_movements_count > 0) {
            return response()->json(['message' => 'No se puede eliminar: El producto tiene movimientos de inventario asociados.'], 409);
        }

        if ($product->production_orders_count > 0) {
            return response()->json(['message' => 'No se puede eliminar: El producto tiene órdenes de producción asociadas.'], 409);
        }

        $product->delete();
        return response()->noContent();
    }

    public function adjustStock(Request $request, TpspProduct $product)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|numeric',
            'type' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validatedData['quantity'] == 0) {
            return response()->json(['message' => 'La cantidad a ajustar no puede ser cero.'], 422);
        }

        try {
            DB::beginTransaction();

            $product->stock += $validatedData['quantity'];
            
            if ($product->stock < 0) {
                return response()->json(['message' => 'El ajuste resultaría en un stock negativo inválido.'], 422);
            }
            
            $product->save();

            tpspInventoryMovement::create([
                'product_id' => $product->id,
                'quantity' => $validatedData['quantity'],
                'type' => $validatedData['type'],
                'notes' => $validatedData['notes'],
                'reference_type' => tpspProduct::class,
                'reference_id' => $product->id,
            ]);

            DB::commit();

            $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null;
            return response()->json($product);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al ajustar stock: ' . $e->getMessage()], 500);
        }
    }

    public function movements(TpspProduct $product)
    {
        $movements = tpspInventoryMovement::where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($movements);
    }
}