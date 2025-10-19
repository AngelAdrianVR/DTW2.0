<?php

namespace App\Http\Controllers;

use App\Models\WebContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WebContentController extends Controller
{
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
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:portfolio,own_projects,client_logos,advertising',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'link_url' => 'nullable|url',
            'images' => 'required|array',
            'images.*' => 'image|max:2048', // 2MB Max per image
            'is_published' => 'boolean',
            'end_date' => 'nullable|date',
        ]);

        $webContent = WebContent::create($request->except('images'));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $webContent->addMedia($file)->toMediaCollection($request->type);
            }
        }

        return redirect()->back()->with('success', 'Contenido agregado exitosamente.');
    }

    public function update(Request $request, WebContent $webContent)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'link_url' => 'nullable|url',
            'is_published' => 'boolean',
            'end_date' => 'nullable|date',
        ]);

        $webContent->update($request->all());

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
}
