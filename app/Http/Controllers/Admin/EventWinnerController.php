<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventWinner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ganadores de un evento.
 *
 * Se capturan a mano (no hay sorteo automático). Si el ganador salió de un
 * registro del formulario, `event_registration_id` lo vincula; si llegó por otro
 * canal, se captura nombre y negocio a mano y el vínculo queda en null.
 */
class EventWinnerController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validatedData($request);

        $registration = null;

        if (! empty($data['event_registration_id'])) {
            $registration = $event->registrations()->find($data['event_registration_id']);
            abort_unless($registration, 404);
        }

        $event->winners()->create([
            'event_registration_id' => $registration?->id,
            'name' => $data['name'],
            'business_name' => ($data['business_name'] ?? null) ?: $registration?->business_name,
            'spanish_prize' => $data['spanish_prize'],
            'english_prize' => $data['english_prize'] ?? null,
            'spanish_note' => $data['spanish_note'] ?? null,
            'english_note' => $data['english_note'] ?? null,
            'awarded_at' => $data['awarded_at'] ?? $event->event_date,
            'is_published' => (bool) ($data['is_published'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return back()->with('flash', [
            'message' => 'Ganador agregado al evento.',
            'type' => 'success',
        ]);
    }

    public function update(Request $request, Event $event, EventWinner $winner): RedirectResponse
    {
        abort_unless($winner->event_id === $event->id, 404);

        $data = $this->validatedData($request);
        unset($data['event_registration_id']);

        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $winner->update($data);

        return back()->with('flash', [
            'message' => 'Ganador actualizado.',
            'type' => 'success',
        ]);
    }

    public function destroy(Event $event, EventWinner $winner): RedirectResponse
    {
        abort_unless($winner->event_id === $event->id, 404);

        $winner->delete();

        return back()->with('flash', [
            'message' => 'Ganador eliminado.',
            'type' => 'success',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'event_registration_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'spanish_prize' => ['required', 'string', 'max:255'],
            'english_prize' => ['nullable', 'string', 'max:255'],
            'spanish_note' => ['nullable', 'string', 'max:255'],
            'english_note' => ['nullable', 'string', 'max:255'],
            'awarded_at' => ['nullable', 'date'],
            'is_published' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
