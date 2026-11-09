<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Portada de carga inicial de la raíz (`/`): el tema se elige desde el
 * back-office y se guarda en `site_settings`.
 */
class SplashThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_splash_uses_day_of_the_dead_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LandingPage/Splash')
                ->where('theme', 'day-of-the-dead')
            );
    }

    public function test_the_admin_can_switch_to_buen_fin_and_back_to_day_of_the_dead(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put('/admin/splash-theme', ['theme' => 'buen-fin'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('buen-fin', SiteSetting::splashTheme());

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('theme', 'buen-fin'));

        // Día de Muertos sigue disponible para reactivarlo cuando se quiera.
        $this->actingAs($admin)
            ->put('/admin/splash-theme', ['theme' => 'day-of-the-dead'])
            ->assertRedirect();

        $this->assertSame('day-of-the-dead', SiteSetting::splashTheme());
    }

    public function test_the_admin_panel_exposes_the_available_splash_themes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/web-contents')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WebContent/Index')
                ->where('splashTheme', 'day-of-the-dead')
                ->has('splashThemes', 4)
                ->where('splashThemes.0.value', 'day-of-the-dead')
                ->where('splashThemes.1.value', 'buen-fin')
                ->where('splashThemes.2.value', 'christmas')
            );
    }

    public function test_the_admin_can_activate_the_christmas_splash(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put('/admin/splash-theme', ['theme' => 'christmas'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('christmas', SiteSetting::splashTheme());

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('theme', 'christmas'));
    }

    public function test_an_unknown_splash_theme_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/splash-theme', ['theme' => 'not-a-theme'])
            ->assertSessionHasErrors('theme');

        $this->assertNull(SiteSetting::get('splash_theme'));
    }

    public function test_guests_cannot_change_the_splash_theme(): void
    {
        $this->put('/admin/splash-theme', ['theme' => 'buen-fin'])
            ->assertRedirect('/login');

        $this->assertNull(SiteSetting::get('splash_theme'));
    }
}
