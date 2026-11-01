<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away_from_the_admin_events(): void
    {
        $this->get('/admin/events')->assertStatus(302);
        $this->post('/admin/events', [])->assertStatus(302);
    }

    public function test_the_events_panel_lists_published_and_draft_events(): void
    {
        $event = $this->makeEvent();

        $response = $this->actingAs(User::factory()->create())->get('/admin/events');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Events/Index')
            ->has('events.data', 1)
            ->where('events.data.0.slug', $event->slug)
            ->where('events.data.0.status', Event::STATUS_OPEN)
            ->where('events.data.0.registrations_count', 0)
            ->where('filters.status', 'all')
        );
    }

    public function test_an_event_can_be_created(): void
    {
        $response = $this->actingAs(User::factory()->create())->post('/admin/events', [
            'spanish_title' => 'Reto de automatización 2026',
            'english_title' => 'Automation challenge 2026',
            'spanish_challenge' => 'Automatiza tu inventario.',
            'spanish_prizes_summary' => 'Sitio web completo.',
            'custom_field_label_es' => '¿Qué proceso automatizarías?',
            'custom_field_required' => true,
            'is_published' => true,
            'sort_order' => 2,
        ]);

        $response->assertRedirect(route('admin.events.index'));

        $event = Event::firstOrFail();

        $this->assertSame('reto-de-automatizacion-2026', $event->slug);
        $this->assertTrue($event->is_published);
        $this->assertTrue($event->custom_field_required);
        $this->assertSame(2, $event->sort_order);
    }

    public function test_an_event_can_be_updated_without_changing_its_slug(): void
    {
        $event = $this->makeEvent(['slug' => 'reto-2026']);

        $response = $this->actingAs(User::factory()->create())->put('/admin/events/' . $event->id, [
            'slug' => 'reto-2026',
            'spanish_title' => 'Reto 2026 (actualizado)',
            'is_published' => false,
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('admin.events.index'));

        $event->refresh();

        $this->assertSame('Reto 2026 (actualizado)', $event->spanish_title);
        $this->assertSame('reto-2026', $event->slug);
        $this->assertFalse($event->is_published);
        $this->assertSame(5, $event->sort_order);
    }

    public function test_the_publish_state_can_be_toggled(): void
    {
        $event = $this->makeEvent(['is_published' => true]);

        $this->actingAs(User::factory()->create())
            ->patch('/admin/events/' . $event->id . '/publish')
            ->assertSessionHas('flash');

        $this->assertFalse($event->fresh()->is_published);
    }

    public function test_the_participants_page_lists_the_registrations(): void
    {
        $event = $this->makeEvent();

        $event->registrations()->create([
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'locale' => 'es',
            'custom_field_label' => '¿Qué problema resuelves?',
            'custom_answer' => 'Automatizar el inventario',
        ]);

        $response = $this->actingAs(User::factory()->create())->get('/admin/events/' . $event->id);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Events/Show')
            ->where('event.slug', $event->slug)
            ->has('registrations.data', 1)
            ->where('registrations.data.0.name', 'Ana Torres')
            ->has('winners', 0)
        );
    }

    public function test_a_registration_can_be_deleted(): void
    {
        $event = $this->makeEvent();

        $registration = $event->registrations()->create([
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'locale' => 'es',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete('/admin/events/' . $event->id . '/registrations/' . $registration->id)
            ->assertSessionHas('flash');

        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_deleting_an_event_removes_its_registrations(): void
    {
        $event = $this->makeEvent();

        $event->registrations()->create([
            'name' => 'Ana Torres',
            'phone' => '3312345678',
            'locale' => 'es',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete('/admin/events/' . $event->id)
            ->assertSessionHas('flash');

        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('event_registrations', 0);
    }

    public function test_winners_can_be_added_updated_and_removed(): void
    {
        $user = User::factory()->create();
        $event = $this->makeEvent();

        $registration = $event->registrations()->create([
            'name' => 'Ana Torres',
            'business_name' => 'Ferretería del Norte',
            'phone' => '3312345678',
            'locale' => 'es',
        ]);

        $this->actingAs($user)->post('/admin/events/' . $event->id . '/winners', [
            'event_registration_id' => $registration->id,
            'name' => 'Ana Torres',
            'spanish_prize' => 'Sitio web completo',
            'is_published' => true,
            'sort_order' => 0,
        ])->assertSessionHasNoErrors();

        $winner = $event->winners()->firstOrFail();

        $this->assertSame($registration->id, $winner->event_registration_id);
        $this->assertSame('Ferretería del Norte', $winner->business_name);
        $this->assertSame($event->event_date?->toDateString(), $winner->awarded_at?->toDateString());

        $this->actingAs($user)->put('/admin/events/' . $event->id . '/winners/' . $winner->id, [
            'name' => 'Ana Torres',
            'spanish_prize' => 'Sitio web y auditoría',
            'is_published' => false,
            'sort_order' => 1,
        ])->assertSessionHasNoErrors();

        $winner->refresh();

        $this->assertSame('Sitio web y auditoría', $winner->spanish_prize);
        $this->assertFalse($winner->is_published);
        $this->assertSame(1, $winner->sort_order);

        $this->actingAs($user)
            ->delete('/admin/events/' . $event->id . '/winners/' . $winner->id)
            ->assertSessionHas('flash');

        $this->assertDatabaseCount('event_winners', 0);
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
            'custom_field_required' => false,
            'is_published' => true,
        ], $overrides));
    }
}
