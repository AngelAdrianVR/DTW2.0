<?php

namespace Tests\Feature;

use App\Models\WebContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingSolutionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shares_the_solutions_from_config(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Index')
            ->has('solutions', count(config('dtw.solutions')))
            ->where('solutions.0.slug', config('dtw.solutions.0.slug'))
            ->has('solutions.0.title_key')
            ->has('solutions.0.bullet_keys')
        );
    }

    public function test_solutions_gallery_renders_the_shared_props(): void
    {
        $response = $this->get('/soluciones');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Solutions/Index')
            ->has('portfolio', 0)
            ->has('solutions', count(config('dtw.solutions')))
            ->has('contact.email')
            ->where('contact.email', config('dtw.contact_email'))
            ->has('siteUrl')
            ->has('metaImage')
        );
    }

    public function test_solutions_gallery_lists_every_published_client_project_in_order(): void
    {
        $this->createPortfolioProject('Segundo proyecto', 'Second project', 2);

        $this->createPortfolioProject('Primer proyecto', 'First project', 1);

        // Sin publicar: no debe aparecer en la galería.
        WebContent::create([
            'type' => 'portfolio',
            'spanish_title' => 'Proyecto oculto',
            'english_title' => 'Hidden project',
            'is_published' => false,
            'sort_order' => 3,
        ]);

        // Otro tipo de contenido de la landing tampoco se mezcla con los proyectos.
        WebContent::create([
            'type' => 'own_projects',
            'spanish_title' => 'Proyecto propio',
            'english_title' => 'Own project',
            'is_published' => true,
            'sort_order' => 4,
        ]);

        $response = $this->get('/soluciones');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Solutions/Index')
            ->has('portfolio', 2)
            ->where('portfolio.0.english_title', 'First project')
            ->where('portfolio.0.spanish_title', 'Primer proyecto')
            ->where('portfolio.1.english_title', 'Second project')
        );
    }

    public function test_a_project_of_the_gallery_opens_its_detail_page(): void
    {
        $project = $this->createPortfolioProject('Proyecto con ficha', 'Project with detail', 1);

        $response = $this->get("/landing-projects/{$project->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/ProjectDetails')
            ->where('project.id', $project->id)
            ->where('project.english_title', 'Project with detail')
        );
    }

    public function test_solution_detail_pages_are_no_longer_available(): void
    {
        foreach (config('dtw.solutions') as $solution) {
            $this->get("/soluciones/{$solution['slug']}")->assertNotFound();
        }
    }

    public function test_an_unknown_solution_returns_404(): void
    {
        $this->get('/soluciones/esto-no-existe')->assertNotFound();
    }

    /**
     * Crea un proyecto de cliente publicado tal como lo guarda el back-office.
     */
    private function createPortfolioProject(string $spanishTitle, string $englishTitle, int $sortOrder): WebContent
    {
        return WebContent::create([
            'type' => 'portfolio',
            'spanish_title' => $spanishTitle,
            'english_title' => $englishTitle,
            'is_published' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
