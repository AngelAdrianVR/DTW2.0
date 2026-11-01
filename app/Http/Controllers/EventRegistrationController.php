<?php

namespace App\Http\Controllers;

use App\Mail\EventRegistrationNotification;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Registro público a un evento.
 *
 * El formulario vive en la página del evento (/eventos/{slug}) y sólo acepta
 * envíos mientras la ventana de registro esté abierta. Un teléfono equivale a
 * un boleto por evento: así el sorteo no se puede inflar repitiendo el envío.
 */
class EventRegistrationController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published, 404);

        if (! $event->can_register) {
            return back()->with('flash', [
                'message' => trans('events.closed'),
                'type' => 'error',
            ]);
        }

        $locale = in_array($request->input('locale'), ['es', 'en'], true)
            ? $request->input('locale')
            : 'es';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'custom_answer' => $event->custom_field_required
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'],
            'locale' => ['nullable', 'string', 'in:es,en'],
            // Honeypot: invisible para personas, tentador para bots.
            'website' => ['nullable', 'prohibited'],
        ]);

        $phone = preg_replace('/\D+/', '', (string) $validated['phone']) ?? '';

        if (strlen($phone) < 10) {
            throw ValidationException::withMessages([
                'phone' => trans('events.phone_invalid'),
            ]);
        }

        if ($event->registrations()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => trans('events.phone_taken'),
            ]);
        }

        $registration = $event->registrations()->create([
            'name' => trim($validated['name']),
            'business_name' => trim((string) ($validated['business_name'] ?? '')) ?: null,
            'phone' => $phone,
            // Snapshot de la pregunta: si la etiqueta cambia, este registro no.
            'custom_field_label' => $event->customFieldLabelFor($locale),
            'custom_answer' => trim((string) ($validated['custom_answer'] ?? '')) ?: null,
            'locale' => $locale,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        $this->notifyTeam($registration);

        return back()->with('flash', [
            'message' => trans('events.registered'),
            'type' => 'success',
        ]);
    }

    /**
     * Avisa al equipo por correo. Nunca interrumpe el registro: si el correo
     * falla, el participante ya quedó guardado y sólo se registra un warning.
     */
    private function notifyTeam(EventRegistration $registration): void
    {
        $recipient = config('dtw.lead_email');

        if (empty($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new EventRegistrationNotification($registration));
        } catch (\Throwable $e) {
            Log::warning(
                'No se pudo enviar la notificación del registro #' . $registration->id . ': ' . $e->getMessage()
            );
        }
    }
}
