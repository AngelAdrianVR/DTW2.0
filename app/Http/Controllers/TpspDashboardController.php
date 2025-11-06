<?php

namespace App\Http\Controllers;

use App\Models\tpspProduct;
use Illuminate\Http\Request;
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
        // Cargar todos los productos para la cuadrícula
        // Asumimos que tpspProduct tiene 'image_url' o lo gestionas en el modelo.
        $products = tpspProduct::orderBy('name')->with('media')
            ->select('id', 'name', 'stock') // Solo los campos necesarios
            ->get();
        
        return Inertia::render('Tpsp/PublicInventory', [
            'products' => $products,
        ]);
    }
}
