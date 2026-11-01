<?php

namespace Tests\Feature;

use App\Mail\EventRegistrationNotification;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_participant_can_register_to_an_open_event(): void
    {
        Mail::fake();

        $event = $this->makeEvent();

        $response = $this->from('/eventos/' . $event->slug)->post('/eventos/' . $event->slug . '/registro', [
            'name' => 'Ana Torres',
            'business_name' => 'Ferretería del Norte',
            'phone' => '33 1234 5678',
            'custom_answer' => 'Automatizar el inventario',
            'locale' => 'es',
        ]);

        $response->assertRedirect('/eventos/' . $event->slug);
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('flash');

        $registration = EventRegistration::firstOrFail();

        $this->assertSame('Ana Torres', $registration->name);
        $this->assertSame('Ferretería del Norte', $registration->business_name);
        // El teléfono se guarda sólo con dígitos para que wa.me funcione.
        $this->assertSame('3312345678', $registration->phone);
        $this->assertSame('es', $registration->locale);
        // Snapshot de la pregunta variable del evento.
        $this->assertSame('¿Qué problema resuelve tu negocio?', $registration->custom_field_label);
        $this->assertSame('Automatizar el inventario', $registration->custom_answer);

        Mail::assertSent(EventRegistrationNotification::class, function (EventRegistrationNotification $mail) use ($registration) {
            return $mail->hasTo(config('dtw.lead_email')) && $mail->registration->is($registration);
        });
    }

    public function test_the_same_phone_cannot_register_twice_to_the_same_event(): void
    {
        Mail::fake();

        $event = $this->makeEvent();

        $payload = [
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'custom_answer' => 'Automatizar el inventario',
            'locale' => 'es',
        ];

        $this->post('/eventos/' . $event->slug . '/registro', $payload)->assertSessionHasNoErrors();

        $response = $this->post('/eventos/' . $event->slug . '/registro', [
            ...$payload,
            'name' => 'Ana Torres (repetida)',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('event_registrations', 1);
        Mail::assertSentCount(1);
    }

    public function test_registration_is_rejected_when_the_window_is_closed(): void
    {
        Mail::fake();

        $event = $this->makeEvent([
            'registration_starts_at' => now()->subMonth(),
            'registration_ends_at' => now()->subDay(),
        ]);

        $response = $this->from('/eventos/' . $event->slug)->post('/eventos/' . $event->slug . '/registro', [
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'custom_answer' => 'Automatizar el inventario',
        ]);

        $response->assertRedirect('/eventos/' . $event->slug);
        $response->assertSessionHas('flash');
        $this->assertDatabaseCount('event_registrations', 0);
        Mail::assertNothingSent();
    }

    public function test_the_custom_answer_is_required_when_the_event_marks_it(): void
    {
        Mail::fake();

        $event = $this->makeEvent(['custom_field_required' => true]);

        $response = $this->post('/eventos/' . $event->slug . '/registro', [
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'custom_answer' => '',
        ]);

        $response->assertSessionHasErrors('custom_answer');
        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_a_short_phone_number_is_rejected(): void
    {
        Mail::fake();

        $event = $this->makeEvent();

        $response = $this->post('/eventos/' . $event->slug . '/registro', [
            'name' => 'Ana Torres',
            'phone' => '1234',
            'custom_answer' => 'Automatizar el inventario',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_draft_events_do_not_accept_registrations(): void
    {
        Mail::fake();

        $event = $this->makeEvent(['is_published' => false]);

        $this->post('/eventos/' . $event->slug . '/registro', [
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'custom_answer' => 'Automatizar el inventario',
        ])->assertNotFound();

        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_a_failing_mail_does_not_lose_the_registration(): void
    {
        // Se fuerza un fallo del correo: el registro debe guardarse igual.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $event = $this->makeEvent();

        $this->post('/eventos/' . $event->slug . '/registro', [
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'custom_answer' => 'Automatizar el inventario',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('event_registrations', 1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'slug' => 'reto-abierto',
            'spanish_title' => 'Reto de automatización 2026',
            'english_title' => 'Automation challenge 2026',
            'spanish_challenge' => 'Cuéntanos qué proceso quieres automatizar.',
            'event_date' => now()->subWeek()->toDateString(),
            'registration_starts_at' => now()->subWeek(),
            'registration_ends_at' => now()->addMonth(),
            'custom_field_label_es' => '¿Qué problema resuelve tu negocio?',
            'custom_field_label_en' => 'What problem does your business solve?',
            'custom_field_required' => true,
            'is_published' => true,
        ], $overrides));
    }
}
