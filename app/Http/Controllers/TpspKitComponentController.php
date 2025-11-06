<?php

namespace App\Http\Controllers;

use App\Models\tpspKitComponent;
use App\Models\tpspProduct;
use Illuminate\Http\Request;

class TpspKitComponentController extends Controller
{
    /**
     * Muestra los componentes de un kit específico.
     * Usado por: KitsTab.vue
     */
    public function index(tpspProduct $product)
    {
        // Asegurarse de que el producto es un kit
        if (!$product->is_kit) {
            return response()->json(['message' => 'Este producto no es un kit'], 400);
        }

        // Cargar los componentes con la información del producto (component_product)
        return $product->components()->with('componentProduct')->get();
    }

    /**
     * Agrega un componente a un kit.
     * Usado por: KitsTab.vue
     */
    public function store(Request $request, TpspProduct $product)
    {
        $validatedData = $request->validate([
            'component_product_id' => 'required|exists:tpsp_products,id',
            'quantity_required' => 'required|numeric|min:0.01',
        ]);

        // Asegurarse de que el producto es un kit
        if (!$product->is_kit) {
            return response()->json(['message' => 'Este producto no es un kit'], 400);
        }

        // Evitar duplicados
        $existing = $product->components()
            ->where('component_product_id', $validatedData['component_product_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Este componente ya existe en el kit'], 422);
        }

        $component = $product->components()->create($validatedData);

        return response()->json($component, 201);
    }

    /**
     * Actualiza la cantidad de un componente en un kit.
     * Ruta 'shallow': /tpsp/components/{component}
     */
    public function update(Request $request, TpspKitComponent $component)
    {
        $validatedData = $request->validate([
            'quantity_required' => 'required|numeric|min:0.01',
        ]);

        $component->update($validatedData);

        return response()->json($component);
    }

    /**
     * Elimina un componente de un kit.
     * Ruta 'shallow': /tpsp/components/{component}
     */
    public function destroy(TpspKitComponent $component)
    {
        $component->delete();
        return response()->noContent();
    }
}
