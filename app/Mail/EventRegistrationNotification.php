<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso interno que recibe el equipo de DTW cuando alguien se registra a un
 * evento desde la página pública.
 *
 * Se envía de forma síncrona y protegido con try/catch en el controlador: si el
 * correo falla, el registro se guarda igualmente.
 */
class EventRegistrationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EventRegistration $registration)
    {
    }

    public function envelope(): Envelope
    {
        $event = $this->registration->event;

        return new Envelope(
            subject: 'Nuevo registro · '
                . ($event?->spanish_title ?: 'Evento DTW')
                . ' · '
                . $this->registration->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-registration',
            with: [
                'registration' => $this->registration,
                'event' => $this->registration->event,
            ],
        );
    }
}
