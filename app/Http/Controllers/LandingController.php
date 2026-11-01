<?php

namespace App\Http\Controllers;

use App\Models\WebContent;
use Inertia\Inertia;

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
            ...$this->sharedProps(),
        ]);
    }

    /**
     * Galería completa de soluciones (/soluciones).
     */
    public function solutions()
    {
        return Inertia::render('LandingPage/Solutions/Index', $this->sharedProps());
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
            'project' => $project,
            ...$this->sharedProps(),
        ]);
    }

    /**
     * Soluciones definidas en config/dtw.php (fuente única de datos).
     *
     * @return array<int, array<string, mixed>>
     */
    private function solutionDefinitions(): array
    {
        return array_values((array) config('dtw.solutions', []));
    }

    /**
     * Props compartidas por todas las vistas públicas de la landing.
     *
     * @return array<string, mixed>
     */
    private function sharedProps(): array
    {
        return [
            'solutions' => $this->solutionDefinitions(),
            // Datos de contacto reales centralizados en config/dtw.php
            'contact' => [
                'email' => config('dtw.contact_email'),
                'whatsapp' => config('dtw.whatsapp'),
                'whatsapp_display' => config('dtw.whatsapp_display'),
            ],
            // Metadatos para SEO / Open Graph
            'siteUrl' => rtrim((string) config('app.url'), '/'),
            'metaImage' => asset('images/branding.webp'),
        ];
    }
}
