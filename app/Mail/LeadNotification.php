<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso interno que recibe el equipo de DTW cuando entra un lead
 * desde el formulario de la landing.
 *
 * Se envía de forma síncrona y protegido con try/catch en el controlador:
 * si el correo falla, la solicitud del prospecto se guarda igualmente.
 */
class LeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote)
    {
    }

    public function envelope(): Envelope
    {
        $company = $this->quote->company ?: $this->quote->client_name;

        return new Envelope(
            subject: 'Nuevo lead web · ' . $company . ' · ' . $this->quote->quote_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.lead-notification',
            with: [
                'quote' => $this->quote,
                'calculator' => $this->quote->calculator_payload,
            ],
        );
    }
}
