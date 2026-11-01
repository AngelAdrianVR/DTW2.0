<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Back-office de eventos: listado, alta/edición, publicación y participantes.
 *
 * El estado (borrador, próximo, abierto, cerrado, finalizado) no se guarda:
 * lo calcula el modelo a partir de `is_published` y la ventana de registro.
 */
class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $events = Event::query()
            ->with('media')
            ->withCount([
                'registrations',
                // Alias acordado con el modelo: `winners_count` = publicados.
                'publishedWinners as winners_count',
                'winners as winners_total',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('spanish_title', 'like', "%{$search}%")
                        ->orWhere('english_title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'filters' => [
                'search' => $search,
                'status' => $status ?: 'all',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->withDefaults($this->validatedData($request));

        $data['slug'] = Event::makeSlug($data['slug'] ?? $data['spanish_title']);

        $event = Event::create($data);
        $this->storeCover($request, $event);

        return redirect()->route('admin.events.index')->with('flash', [
            'message' => 'Evento creado correctamente.',
            'type' => 'success',
        ]);
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Edit', [
            'event' => $event->load('media')->loadCount([
                'registrations',
                'publishedWinners as winners_count',
            ]),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $this->withDefaults($this->validatedData($request));

        $data['slug'] = Event::makeSlug($data['slug'] ?? $event->spanish_title, $event->id);

        $event->update($data);

        // El admin puede quitar la portada sin subir otra.
        if ($request->boolean('remove_image')) {
            $event->clearMediaCollection('events');
        }

        $this->storeCover($request, $event);

        return redirect()->route('admin.events.index')->with('flash', [
            'message' => 'Evento actualizado.',
            'type' => 'success',
        ]);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return back()->with('flash', [
            'message' => 'Evento eliminado con sus registros.',
            'type' => 'success',
        ]);
    }

    /**
     * Participantes y ganadores de un evento.
     */
    public function show(Request $request, Event $event): Response
    {
        $search = trim((string) $request->input('search'));

        $registrations = $event->registrations()
            ->with('winner')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('custom_answer', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Events/Show', [
            'event' => $event->load('media')->loadCount([
                'registrations',
                'publishedWinners as winners_count',
            ]),
            'registrations' => $registrations,
            'winners' => $event->winners()->with('registration')->get(),
            'filters' => ['search' => $search],
        ]);
    }

    public function togglePublish(Event $event): RedirectResponse
    {
        $event->update(['is_published' => ! $event->is_published]);

        return back()->with('flash', [
            'message' => $event->is_published
                ? 'Evento publicado: ya se ve en el sitio.'
                : 'Evento oculto: sólo se ve en el panel.',
            'type' => 'success',
        ]);
    }

    /**
     * Reglas compartidas por alta y edición.
     *
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $endsRules = $request->filled('registration_starts_at')
            ? ['nullable', 'date', 'after_or_equal:registration_starts_at']
            : ['nullable', 'date'];

        return $request->validate([
            'spanish_title' => ['required', 'string', 'max:255'],
            'english_title' => ['nullable', 'string', 'max:255'],
            'spanish_description' => ['nullable', 'string', 'max:5000'],
            'english_description' => ['nullable', 'string', 'max:5000'],
            'spanish_challenge' => ['nullable', 'string', 'max:5000'],
            'english_challenge' => ['nullable', 'string', 'max:5000'],
            'spanish_prizes_summary' => ['nullable', 'string', 'max:2000'],
            'english_prizes_summary' => ['nullable', 'string', 'max:2000'],
            'event_date' => ['nullable', 'date'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => $endsRules,
            'custom_field_label_es' => ['nullable', 'string', 'max:255'],
            'custom_field_label_en' => ['nullable', 'string', 'max:255'],
            'custom_field_placeholder_es' => ['nullable', 'string', 'max:255'],
            'custom_field_placeholder_en' => ['nullable', 'string', 'max:255'],
            'custom_field_required' => ['boolean'],
            'is_published' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'slug' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
        ]);
    }

    /**
     * Limpia el payload: saca el archivo, normaliza vacíos a null y asegura
     * los booleanos que espera la tabla.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withDefaults(array $data): array
    {
        unset($data['image'], $data['remove_image']);

        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);

        $data['custom_field_required'] = (bool) ($data['custom_field_required'] ?? false);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /**
     * Guarda la portada en la colección `events` (singleFile: reemplaza la
     * anterior). Si el archivo falla, el evento queda guardado igual.
     */
    private function storeCover(Request $request, Event $event): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        try {
            $event->addMediaFromRequest('image')->toMediaCollection('events');
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar la portada del evento ' . $event->id . ': ' . $e->getMessage());
        }
    }
}
