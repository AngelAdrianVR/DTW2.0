<?php

namespace Tests\Feature;

use App\Models\WebContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_shows_the_thematic_splash(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Splash')
        );
    }

    public function test_landing_page_renders_with_contact_and_seo_props(): void
    {
        $response = $this->get('/inicio');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Index')
            ->has('contact')
            ->where('contact.email', config('dtw.contact_email'))
            ->where('contact.whatsapp', config('dtw.whatsapp'))
            ->has('contact.whatsapp_display')
            ->has('siteUrl')
            ->has('metaImage')
            ->has('portfolio')
            ->has('ownProjects')
            ->has('clientLogos')
            ->has('advertising')
            ->has('events')
        );
    }

    public function test_landing_page_shares_the_services_from_config(): void
    {
        $response = $this->get('/inicio');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Index')
            ->has('solutions', count(config('dtw.solutions')))
            ->where('solutions.0.slug', config('dtw.solutions.0.slug'))
            ->has('solutions.0.title_key')
            ->has('solutions.0.bullet_keys')
        );
    }

    public function test_landing_page_shares_only_the_published_own_projects(): void
    {
        $this->createOwnProject('Segundo proyecto propio', 'Second own project', 2);
        $this->createOwnProject('Primer proyecto propio', 'First own project', 1);
        $this->createOwnProject('Proyecto propio oculto', 'Hidden own project', 0, false);

        $response = $this->get('/inicio');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Index')
            ->has('ownProjects', 2)
            ->where('ownProjects.0.english_title', 'First own project')
            ->where('ownProjects.1.english_title', 'Second own project')
        );
    }

    public function test_landing_page_keeps_spanish_translations_by_default(): void
    {
        $response = $this->get('/inicio');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Index')
            ->where('locale', 'es')
            ->where('translations.Reset', 'Reiniciar')
            ->where('translations.Problems', 'Problemas')
        );
    }

    public function test_landing_page_loads_english_translations(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/inicio');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Index')
            ->where('locale', 'en')
            ->where('translations.Reset', 'Reset')
            ->where('translations.Problems', 'Problems')
            ->where('translations.Request a free diagnosis', 'Request a free diagnosis')
        );
    }

    public function test_the_spanish_and_english_translation_files_share_the_same_keys(): void
    {
        $spanish = json_decode(file_get_contents(lang_path('es/es.json')), true);
        $english = json_decode(file_get_contents(lang_path('en/en.json')), true);

        $this->assertIsArray($spanish);
        $this->assertIsArray($english);

        $missingInSpanish = array_diff(array_keys($english), array_keys($spanish));
        $missingInEnglish = array_diff(array_keys($spanish), array_keys($english));

        $this->assertSame([], array_values($missingInSpanish), 'Claves presentes en inglés y ausentes en español.');
        $this->assertSame([], array_values($missingInEnglish), 'Claves presentes en español y ausentes en inglés.');
    }

    /**
     * Crea un proyecto propio (gratis para la comunidad) tal como lo guarda el
     * back-office.
     */
    private function createOwnProject(
        string $spanishTitle,
        string $englishTitle,
        int $sortOrder,
        bool $published = true,
    ): WebContent {
        return WebContent::create([
            'type' => 'own_projects',
            'spanish_title' => $spanishTitle,
            'english_title' => $englishTitle,
            'is_published' => $published,
            'sort_order' => $sortOrder,
        ]);
    }
}
