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
        
        // Procesar los logos de clientes para obtener una lista plana
        $clientGroups = WebContent::where('type', 'client_logos')
                             ->where('is_published', true)
                             ->orderBy('sort_order')
                             ->get();

        $clientLogos = $clientGroups->flatMap(function ($group) {
            return $group->media->map(function ($media) {
                return [
                    'name' => $media->name,
                    // CORRECCIÓN: El método correcto es getUrl()
                    'url' => $media->getUrl(),
                ];
            });
        });
        
        $advertising = WebContent::where('type', 'advertising')
                             ->where('is_published', true)
                             ->orderBy('sort_order')
                             ->get();

        return Inertia::render('LandingPage/Index', [
            'portfolio' => $portfolio,
            'ownProjects' => $ownProjects,
            'clientLogos' => $clientLogos, // Enviar la lista de logos procesada
            'advertising' => $advertising,
        ]);    
    }

    /**
     * Muestra la página de detalles para un proyecto específico.
     *
     * @param  \App\Models\WebContent  $project
     * @return \Inertia\Response
     */
    public function showProject(WebContent $project)
    {
        // Asegurarse de que el proyecto es del tipo correcto y está publicado
        if ($project->type !== 'portfolio' || !$project->is_published) {
            abort(404);
        }

        // Cargar las imágenes del proyecto (media) si no están ya cargadas
        $project->load('media');

        return Inertia::render('LandingPage/ProjectDetails', [
            'project' => $project
        ]);
    }
}
