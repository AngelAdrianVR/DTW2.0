<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\WebContent;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        // Obtener contenido publicado y ordenado
        $portfolio = $this->publishedPortfolio();

        $ownProjects = $this->publishedOwnProjects();

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
            // Eventos vigentes + últimas ediciones para la sección de la landing.
            'events' => $this->landingEvents(),
            ...$this->sharedProps(),
        ]);
    }

    /**
     * Galería completa de proyectos (/proyectos).
     *
     * La vista tiene un selector con dos galerías, ambas en el mismo orden del
     * back-office:
     *
     *  - `portfolio`: proyectos desarrollados para clientes (WebContent tipo
     *    `portfolio` publicados). Cada tarjeta enlaza a su ficha
     *    (/landing-projects/{id}).
     *  - `ownProjects`: proyectos propios liberados gratis para la comunidad
     *    (WebContent tipo `own_projects` publicados). Cada tarjeta enlaza al
     *    sitio o repositorio externo guardado en `link_url`.
     */
    public function projects()
    {
        return Inertia::render('LandingPage/Projects/Index', [
            'portfolio' => $this->publishedPortfolio(),
            'ownProjects' => $this->publishedOwnProjects(),
            ...$this->sharedProps(),
        ]);
    }

    /**
     * Página pública de eventos (/eventos).
     *
     * Lista las ediciones publicadas, tanto la que está abierta como el
     * historial. Cada tarjeta enlaza a la ficha del evento, donde vive el
     * formulario de registro.
     */
    public function events()
    {
        $events = Event::query()
            ->published()
            ->with(['media', 'publishedWinners'])
            ->withCount(['registrations', 'publishedWinners as winners_count'])
            ->ordered()
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('LandingPage/Events/Index', [
            'events' => $events,
            ...$this->sharedProps(),
        ]);
    }

    /**
     * Ficha pública de un evento (/eventos/{slug}): reto, premios, ganadores y
     * formulario de registro mientras la ventana esté abierta.
     */
    public function showEvent(Event $event)
    {
        abort_unless($event->is_published, 404);

        $event->load(['media', 'publishedWinners'])->loadCount([
            'registrations',
            'publishedWinners as winners_count',
        ]);

        return Inertia::render('LandingPage/Events/Show', [
            'event' => $event,
            ...$this->sharedProps(),
        ]);
    }

    /**
     * Eventos que se muestran en la portada: se limita la lista para no alargar
     * la landing (el resto vive en /eventos).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event>
     */
    private function landingEvents()
    {
        return Event::query()
            ->published()
            ->with(['media', 'publishedWinners'])
            ->withCount(['registrations', 'publishedWinners as winners_count'])
            ->ordered()
            ->limit(6)
            ->get();
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
     * Proyectos desarrollados para clientes (WebContent tipo `portfolio`)
     * publicados y en el orden definido en el back-office.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\WebContent>
     */
    private function publishedPortfolio()
    {
        return WebContent::where('type', 'portfolio')
            ->where('is_published', true)
            ->with('media') // portada + galería ya cargadas (sin N+1 en image_url)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Proyectos propios (gratis para la comunidad) publicados y en el orden
     * definido en el back-office.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\WebContent>
     */
    private function publishedOwnProjects()
    {
        return WebContent::where('type', 'own_projects')
            ->where('is_published', true)
            ->with('media') // portada + galería ya cargadas (sin N+1 en image_url)
            ->orderBy('sort_order')
            ->get();
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
