<?php

namespace App\Http\Controllers;

use App\Models\tpspProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Importar Log
use Illuminate\Validation\Rule;

class TpspProductController extends Controller
{
    
    /**
     * Muestra una lista de los productos.
     * Usado por: ProductsTab.vue, KitsTab.vue, TpspIndex.vue (modal)
     */
    public function index(Request $request)
    {
        $query = TpspProduct::query();

        // Filtro para el modal de TpspIndex.vue que solo busca kits
        if ($request->boolean('is_kit')) {
            $query->where('is_kit', true);
        }

        // Cargar productos con su imagen principal
        $products = $query->orderBy('name')->get();
        
        // Asumiendo que tu colección de media se llama 'products'
        // y que tu modelo tpspProduct usa el trait HasMedia
        $products->each(function ($product) {
            $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null; // 'thumb' es un ejemplo de conversión
        });

        return $products;
    }

    /**
     * Almacena un nuevo producto.
     * Usado por: ProductsTab.vue
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:tpsp_products,sku',
            'category' => ['required', 'string', Rule::in(['Material', 'Insumo', 'Empaque', 'Kit Terminado'])],
            'unit_of_measure' => ['required', 'string', Rule::in(['Pieza', 'Mililitro', 'Gramo', 'Kit', 'Kilogramo','Metro','Rollo','Litro'])],
            'stock' => 'required|integer|min:0',
            'is_kit' => 'required|boolean',
        ]);

        $product = TpspProduct::create($validatedData);

        // Manejo de la imagen con Spatie Media Library
        if ($request->hasFile('image')) {
            // Asumiendo que tu colección se llama 'products'
            $product->addMediaFromRequest('image')->toMediaCollection('products');
        }

        // Recargar el producto con la URL de la imagen para devolverlo
        $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null;

        return response()->json($product, 201);
    }

    /**
     * Muestra un producto específico.
     */
    public function show(TpspProduct $product)
    {
        // Devolver con la URL de la imagen
        $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null;
        return $product;
    }

    /**
     * Actualiza un producto específico.
     * NOTA: HTML/FormData no soporta PUT/PATCH con archivos de forma nativa.
     * Usaremos un POST desde Vue con un campo _method='PUT'.
     */
    public function update(Request $request, TpspProduct $product)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['nullable', 'string', Rule::unique('tpsp_products', 'sku')->ignore($product->id)],
            'category' => ['required', 'string', Rule::in(['Material', 'Insumo', 'Empaque', 'Kit Terminado'])],
            'unit_of_measure' => ['required', 'string', Rule::in(['Pieza', 'Mililitro', 'Gramo', 'Kit', 'Kilogramo','Metro','Rollo','Litro'])],
            'stock' => 'required|integer|min:0',
            'is_kit' => 'required|boolean',
        ]);
        
        // Log::info('Request data', $request->all());

        $product->update($validatedData);

        // Manejo de la imagen con Spatie Media Library
        if ($request->hasFile('image')) {
            // Limpiar imágenes anteriores si se desea, o simplemente agregar la nueva
            $product->clearMediaCollection('products');
            $product->addMediaFromRequest('image')->toMediaCollection('products');
        }
        
        // Recargar el producto con la URL de la imagen para devolverlo
        $product->load('media'); // Asegurarse de que la media esté cargada
        $product->image_url = $product->getFirstMediaUrl('products', 'thumb') ?: null;

        return response()->json($product);
    }

    /**
     * Elimina un producto.
     */
    public function destroy(TpspProduct $product)
    {
        //--- INICIO: Verificación de Integridad ---
        
        // Cargar las relaciones que podrían bloquear el borrado.
        // Asumo que estas relaciones existen en tu modelo TpspProduct
        // (basado en tus migraciones)
        $product->loadCount(['inventoryMovements', 'productionOrders']);

        // Verificar si hay movimientos de inventario
        if ($product->inventory_movements_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: El producto tiene movimientos de inventario asociados.'
            ], 409); // 409 Conflict
        }

        // Verificar si hay órdenes de producción
        if ($product->production_orders_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: El producto tiene órdenes de producción asociadas.'
            ], 409); // 409 Conflict
        }
        //--- FIN: Verificación de Integridad ---


        // Si pasa las verificaciones, se puede borrar.
        // Los componentes del kit se borrarán en cascada (según tu migración)
        // Spatie Media Library (si está configurado) debería borrar los archivos asociados
        $product->delete();
        
        return response()->noContent();
    }
}
