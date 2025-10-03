<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // Compartimos los mensajes 'flash' de la sesión con el frontend.
            // Esto permite que el componente de Vue reciba el mensaje que enviaste desde el controlador.
            'flash' => function () use ($request) {
                return [
                    'message' => $request->session()->get('flash.message'),
                    'type' => $request->session()->get('flash.type'),
                ];
            },
            'auth' => [
                'user' => $request->user(),
            ],
            // Compartimos el idioma actual y las traducciones
            'locale' => function () {
                return App::getLocale();
            },
            'translations' => function () {
                $locale = App::getLocale();
                // --- CAMBIO CLAVE AQUÍ ---
                // Ajustamos la ruta para que coincida con tu estructura de carpetas (ej. lang/es/es.json)
                $jsonPath = lang_path("{$locale}/{$locale}.json");

                if (!File::exists($jsonPath)) {
                    // Si el archivo de idioma no existe, intentamos la ruta estándar (ej. lang/es.json) como respaldo.
                    $jsonPath = lang_path("{$locale}.json");
                    if (!File::exists($jsonPath)) {
                        return [];
                    }
                }
                
                // Lee el archivo JSON y lo convierte en un array asociativo
                return json_decode(File::get($jsonPath), true);
            },
        ]);
    }
}
