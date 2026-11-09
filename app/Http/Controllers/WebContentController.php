<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\WebContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WebContentController extends Controller
{
    /**
     * Portadas de carga disponibles para la raíz del sitio.
     *
     * @return array<int, array{value: string, label: string, preview: string|null, description: string}>
     */
    private static function splashThemes(): array
    {
        return [
            [
                'value' => 'day-of-the-dead',
                'label' => 'Día de Muertos',
                'preview' => '/images/day-of-the-dead-bg.svg',
                'description' => 'Ofrenda nocturna con cempasúchil, papel picado y mariposas monarca.',
            ],
            [
                'value' => 'buen-fin',
                'label' => 'Buen Fin (13 al 17 de noviembre)',
                'preview' => '/images/buen-fin-bg.svg',
                'description' => 'Fondo festivo rojo con descuentos de hasta 20% y confeti.',
            ],
            [
                'value' => 'christmas',
                'label' => 'Navidad',
                'preview' => '/images/christmas-bg.webp',
                'description' => 'Noche nevada con corona navideña, luces cálidas y nieve cayendo.',
            ],
            [
                'value' => 'halloween',
                'label' => 'Halloween',
                'preview' => null,
                'description' => 'Escena de Halloween con calabazas y murciélagos.',
            ],
        ];
    }

    public function index(Request $request)
    {
        $contents = WebContent::query()
            ->with('media') // Eager load media
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type');

        return Inertia::render('WebContent/Index', [
            'webcontents' => $contents,
            'splashTheme' => SiteSetting::splashTheme(),
            'splashThemes' => self::splashThemes(),
        ]);
    }

    /**
     * Guarda la portada que se muestra al cargar la raíz del sitio (`/`).
     */
    public function updateSplashTheme(Request $request)
    {
        $validated = $request->validate([
            'theme' => [
                'required',
                'string',
                Rule::in(array_column(self::splashThemes(), 'value')),
            ],
        ]);

        SiteSetting::put(SiteSetting::SPLASH_THEME_KEY, $validated['theme']);

        return redirect()->back()->with('success', 'Portada de carga actualizada.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:portfolio,own_projects,client_logos,advertising',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'link_url' => 'nullable|url',
            // La galería deja de ser obligatoria si se sube una portada:
            // un proyecto puede vivir sólo con su imagen de portada.
            'images' => 'required_without:cover|array',
            'images.*' => 'image|max:2048', // 2MB Max per image
            'cover' => 'nullable|image|max:2048',
            'is_published' => 'boolean',
            'end_date' => 'nullable|date',
        ]);

        $webContent = WebContent::create($request->except(['images', 'cover']));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $webContent->addMedia($file)->toMediaCollection($request->type);
            }
        }

        $this->storeCover($request, $webContent);

        return redirect()->back()->with('success', 'Contenido agregado exitosamente.');
    }

    public function update(Request $request, WebContent $webContent)
    {
        $request->validate([
            'spanish_title' => 'nullable|string|max:255',
            'english_title' => 'nullable|string|max:255',
            'spanish_content' => 'nullable|string',
            'english_content' => 'nullable|string',
            'link_url' => 'nullable|url',
            'is_published' => 'boolean',
            'end_date' => 'nullable|date',
            // --- INICIO: Validación para nuevas imágenes ---
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|max:2048',
            // --- FIN: Validación para nuevas imágenes ---
            // --- INICIO: Portada (imagen opcional, una sola) ---
            'cover' => 'nullable|image|max:2048',
            'remove_cover' => 'boolean',
            // --- FIN: Portada ---
        ]);

        $webContent->update($request->except(['new_images', 'cover', 'remove_cover', 'media']));

        // --- INICIO: Lógica para agregar nuevas imágenes ---
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $file) {
                $webContent->addMedia($file)->toMediaCollection($webContent->type);
            }
        }
        // --- FIN: Lógica para agregar nuevas imágenes ---

        // El admin puede quitar la portada sin subir otra.
        if ($request->boolean('remove_cover')) {
            $webContent->clearMediaCollection('cover');
        }

        $this->storeCover($request, $webContent);

        return redirect()->back()->with('success', 'Contenido actualizado.');
    }

    public function destroy(WebContent $webContent)
    {
        $webContent->delete();
        return redirect()->back()->with('success', 'Contenido eliminado.');
    }

    // --- INICIO: Nuevo método para eliminar una imagen específica ---
    public function destroyMedia(Media $media)
    {
        $media->delete();
        return redirect()->back()->with('success', 'Imagen eliminada correctamente.');
    }

    /**
     * Guarda la portada en la colección `cover` (singleFile: reemplaza la
     * anterior). Si el archivo falla, el contenido queda guardado igual.
     */
    private function storeCover(Request $request, WebContent $webContent): void
    {
        if (! $request->hasFile('cover')) {
            return;
        }

        try {
            $webContent->addMediaFromRequest('cover')->toMediaCollection('cover');
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar la portada del contenido ' . $webContent->id . ': ' . $e->getMessage());
        }
    }
}
