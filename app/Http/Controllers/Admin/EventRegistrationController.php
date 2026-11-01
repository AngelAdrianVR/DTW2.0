<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\RedirectResponse;

/**
 * Registros (participantes) de un evento desde el panel.
 *
 * Sólo se permite eliminar: los datos se capturan desde el formulario público.
 */
class EventRegistrationController extends Controller
{
    public function destroy(Event $event, EventRegistration $registration): RedirectResponse
    {
        abort_unless($registration->event_id === $event->id, 404);

        $registration->delete();

        return back()->with('flash', [
            'message' => 'Registro eliminado.',
            'type' => 'success',
        ]);
    }
}
