<?php

namespace Tests\Feature;

use App\Models\WebContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_gallery_renders_the_shared_props(): void
    {
        $response = $this->get('/proyectos');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Projects/Index')
            ->has('portfolio', 0)
            ->has('ownProjects', 0)
            ->has('contact.email')
            ->where('contact.email', config('dtw.contact_email'))
            ->has('siteUrl')
            ->has('metaImage')
        );
    }

    public function test_projects_gallery_lists_every_published_client_project_in_order(): void
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

        $response = $this->get('/proyectos');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Projects/Index')
            ->has('portfolio', 2)
            ->where('portfolio.0.english_title', 'First project')
            ->where('portfolio.0.spanish_title', 'Primer proyecto')
            ->where('portfolio.1.english_title', 'Second project')
            // El proyecto propio no se mezcla con los de cliente.
            ->has('ownProjects', 1)
            ->where('ownProjects.0.english_title', 'Own project')
        );
    }

    public function test_projects_gallery_lists_the_published_community_projects_in_order(): void
    {
        $this->createOwnProject('Segundo proyecto propio', 'Second own project', 2);

        $this->createOwnProject('Primer proyecto propio', 'First own project', 1);

        // Sin publicar: no debe aparecer en la galería de la comunidad.
        WebContent::create([
            'type' => 'own_projects',
            'spanish_title' => 'Proyecto propio oculto',
            'english_title' => 'Hidden own project',
            'is_published' => false,
            'sort_order' => 3,
        ]);

        // Un proyecto de cliente tampoco se mezcla con los de la comunidad.
        $this->createPortfolioProject('Proyecto de cliente', 'Client project', 4);

        $response = $this->get('/proyectos');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Projects/Index')
            ->has('portfolio', 1)
            ->has('ownProjects', 2)
            ->where('ownProjects.0.english_title', 'First own project')
            ->where('ownProjects.0.spanish_title', 'Primer proyecto propio')
            ->where('ownProjects.1.english_title', 'Second own project')
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

    public function test_the_gallery_lives_at_proyectos(): void
    {
        $this->assertSame(url('/proyectos'), route('landing.projects'));
        $this->assertFalse(Route::has('landing.solutions'));
    }

    public function test_the_old_solutions_url_redirects_to_the_gallery(): void
    {
        $this->get('/soluciones')
            ->assertStatus(301)
            ->assertRedirect('/proyectos');
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

    /**
     * Crea un proyecto propio (gratis para la comunidad) publicado.
     */
    private function createOwnProject(string $spanishTitle, string $englishTitle, int $sortOrder): WebContent
    {
        return WebContent::create([
            'type' => 'own_projects',
            'spanish_title' => $spanishTitle,
            'english_title' => $englishTitle,
            'is_published' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
