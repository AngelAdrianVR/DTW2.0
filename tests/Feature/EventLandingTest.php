<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_events_page_lists_only_published_events(): void
    {
        $this->makeEvent(['slug' => 'reto-abierto']);
        $this->makeEvent(['slug' => 'reto-borrador', 'is_published' => false]);

        $response = $this->get('/eventos');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Events/Index')
            ->has('events.data', 1)
            ->where('events.data.0.slug', 'reto-abierto')
            ->where('events.data.0.can_register', true)
            ->has('contact')
        );
    }

    public function test_the_event_page_is_public_while_the_registration_is_open(): void
    {
        $event = $this->makeEvent();

        $response = $this->get('/eventos/' . $event->slug);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Events/Show')
            ->where('event.slug', 'reto-abierto')
            ->where('event.status', 'open')
            ->where('event.can_register', true)
            ->has('event.published_winners', 0)
        );
    }

    public function test_a_draft_event_is_not_public(): void
    {
        $event = $this->makeEvent(['is_published' => false]);

        $this->get('/eventos/' . $event->slug)->assertNotFound();
    }

    public function test_the_status_follows_the_registration_window(): void
    {
        $upcoming = $this->makeEvent([
            'slug' => 'proximo',
            'registration_starts_at' => now()->addWeek(),
            'registration_ends_at' => now()->addMonth(),
        ]);

        $closed = $this->makeEvent([
            'slug' => 'cerrado',
            'registration_starts_at' => now()->subMonth(),
            'registration_ends_at' => now()->subDay(),
        ]);

        $this->assertSame(Event::STATUS_UPCOMING, $upcoming->fresh()->status);
        $this->assertFalse($upcoming->fresh()->can_register);

        $this->assertSame(Event::STATUS_CLOSED, $closed->fresh()->status);
        $this->assertFalse($closed->fresh()->can_register);
    }

    public function test_an_event_with_published_winners_is_finished(): void
    {
        $event = $this->makeEvent([
            'registration_starts_at' => now()->subMonth(),
            'registration_ends_at' => now()->subDay(),
        ]);

        $event->winners()->create([
            'name' => 'Ana Torres',
            'spanish_prize' => 'Sitio web completo',
            'is_published' => true,
        ]);

        $this->assertSame(Event::STATUS_FINISHED, $event->fresh()->status);
    }

    public function test_only_published_winners_are_listed(): void
    {
        $event = $this->makeEvent();

        $event->winners()->create([
            'name' => 'Ganador visible',
            'spanish_prize' => 'Sitio web',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $event->winners()->create([
            'name' => 'Ganador oculto',
            'spanish_prize' => 'Auditoría',
            'is_published' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get('/eventos/' . $event->slug);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('event.published_winners', 1)
            ->where('event.published_winners.0.name', 'Ganador visible')
        );
    }

    public function test_the_home_landing_includes_the_published_events(): void
    {
        $this->makeEvent(['slug' => 'reto-abierto']);
        $this->makeEvent(['slug' => 'reto-borrador', 'is_published' => false]);

        $response = $this->get('/inicio');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Index')
            ->has('events', 1)
            ->where('events.0.slug', 'reto-abierto')
        );
    }

    /**
     * Evento de prueba listo para registrarse.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'slug' => 'reto-abierto',
            'spanish_title' => 'Reto de automatización 2026',
            'english_title' => 'Automation challenge 2026',
            'spanish_description' => 'Automatiza tu operación.',
            'english_description' => 'Automate your operation.',
            'spanish_challenge' => 'Cuéntanos qué proceso quieres automatizar.',
            'english_challenge' => 'Tell us which process you want to automate.',
            'spanish_prizes_summary' => 'Sitio web completo y una auditoría.',
            'english_prizes_summary' => 'A full website and an audit.',
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
