<?php

namespace App\Http\Controllers;

use App\Models\WebContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;

class LandingController extends Controller
{
    public function index()
    {
        // Obtener contenido publicado y ordenado
        $portfolio = WebContent::where('type', 'portfolio')
                               ->where('is_published', true)
                               ->orderBy('sort_order')
                               ->get();

        $ownProjects = WebContent::where('type', 'own_projects')
                                 ->where('is_published', true)
                                 ->orderBy('sort_order')
                                 ->get();
        
        $clients = WebContent::where('type', 'client_logos')
                             ->where('is_published', true)
                             ->orderBy('sort_order')
                             ->get();
                             
        return Inertia::render('LandingPage/Index', [
            'portfolio' => $portfolio,
            'ownProjects' => $ownProjects,
            'clients' => $clients,
        ]);    
    }
}
