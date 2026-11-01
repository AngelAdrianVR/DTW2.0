<?php

namespace Tests\Feature;

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

    public function test_solutions_gallery_renders_with_the_shared_props(): void
    {
        $response = $this->get('/soluciones');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Solutions/Index')
            ->has('solutions', count(config('dtw.solutions')))
            ->has('contact.email')
            ->has('siteUrl')
            ->has('metaImage')
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
}
