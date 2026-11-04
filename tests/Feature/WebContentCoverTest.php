<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Portada de los proyectos del back-office (WebContent).
 *
 * La portada vive en la colección `cover` (singleFile) y manda sobre la
 * galería: `cover_url` la expone y `image_url` cae en ella antes que en la
 * primera imagen de la galería.
 */
class WebContentCoverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_the_cover_wins_over_the_gallery_as_the_primary_image(): void
    {
        $project = $this->createProject();

        $gallery = $project->addMedia(UploadedFile::fake()->image('galeria.jpg'))
            ->toMediaCollection('portfolio');

        $cover = $project->addMedia(UploadedFile::fake()->image('portada.jpg'))
            ->toMediaCollection('cover');

        $project->refresh();

        $this->assertSame($cover->getUrl(), $project->cover_url);
        $this->assertSame($cover->getUrl(), $project->image_url);
        $this->assertNotSame($gallery->getUrl(), $project->image_url);
    }

    public function test_without_a_cover_the_primary_image_is_the_first_gallery_image(): void
    {
        $project = $this->createProject('own_projects');

        $gallery = $project->addMedia(UploadedFile::fake()->image('galeria.jpg'))
            ->toMediaCollection('own_projects');

        $project->refresh();

        $this->assertNull($project->cover_url);
        $this->assertSame($gallery->getUrl(), $project->image_url);
    }

    public function test_a_cover_can_live_alone_without_a_gallery(): void
    {
        $project = $this->createProject();

        $project->addMedia(UploadedFile::fake()->image('portada.jpg'))
            ->toMediaCollection('cover');

        $project->refresh();

        $this->assertNotNull($project->cover_url);
        $this->assertSame($project->cover_url, $project->image_url);
        $this->assertCount(0, $project->getMedia('portfolio'));
    }

    public function test_the_projects_gallery_exposes_the_cover_url(): void
    {
        $project = $this->createProject();

        $project->addMedia(UploadedFile::fake()->image('galeria.jpg'))
            ->toMediaCollection('portfolio');

        $cover = $project->addMedia(UploadedFile::fake()->image('portada.jpg'))
            ->toMediaCollection('cover');

        $response = $this->get('/proyectos');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LandingPage/Projects/Index')
            ->has('portfolio', 1)
            ->where('portfolio.0.cover_url', $cover->getUrl())
            ->where('portfolio.0.image_url', $cover->getUrl())
        );
    }

    public function test_the_admin_can_create_a_project_with_only_a_cover(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/web-contents', [
                'type' => 'portfolio',
                'spanish_title' => 'Proyecto con portada',
                'english_title' => 'Project with cover',
                'is_published' => true,
                'cover' => UploadedFile::fake()->image('portada.jpg'),
            ])
            ->assertRedirect();

        $project = WebContent::firstOrFail();

        $this->assertCount(1, $project->getMedia('cover'));
        $this->assertCount(0, $project->getMedia('portfolio'));
        $this->assertNotNull($project->fresh()->cover_url);
    }

    public function test_the_admin_still_needs_something_to_show_when_creating(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/web-contents', [
                'type' => 'portfolio',
                'spanish_title' => 'Proyecto vacío',
                'is_published' => true,
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(0, WebContent::count());
    }

    public function test_the_admin_can_replace_the_cover(): void
    {
        $project = $this->createProject();

        $project->addMedia(UploadedFile::fake()->image('portada-vieja.jpg'))
            ->toMediaCollection('cover');

        $oldCoverUrl = $project->fresh()->cover_url;

        $this->actingAs(User::factory()->create())
            ->put("/admin/web-contents/{$project->id}", [
                'spanish_title' => $project->spanish_title,
                'is_published' => true,
                'cover' => UploadedFile::fake()->image('portada-nueva.jpg'),
            ])
            ->assertRedirect();

        $project->refresh();

        $this->assertCount(1, $project->getMedia('cover'));
        $this->assertNotSame($oldCoverUrl, $project->cover_url);
    }

    public function test_the_admin_can_remove_the_cover_without_touching_the_gallery(): void
    {
        $project = $this->createProject();

        $gallery = $project->addMedia(UploadedFile::fake()->image('galeria.jpg'))
            ->toMediaCollection('portfolio');

        $project->addMedia(UploadedFile::fake()->image('portada.jpg'))
            ->toMediaCollection('cover');

        $this->actingAs(User::factory()->create())
            ->put("/admin/web-contents/{$project->id}", [
                'spanish_title' => $project->spanish_title,
                'is_published' => true,
                'remove_cover' => true,
            ])
            ->assertRedirect();

        $project->refresh();

        $this->assertCount(0, $project->getMedia('cover'));
        $this->assertCount(1, $project->getMedia('portfolio'));
        // Sin portada, la imagen principal vuelve a ser la de la galería.
        $this->assertNull($project->cover_url);
        $this->assertSame($gallery->getUrl(), $project->image_url);
    }

    /**
     * Proyecto publicado tal como lo guarda el back-office.
     */
    private function createProject(string $type = 'portfolio'): WebContent
    {
        return WebContent::create([
            'type' => $type,
            'spanish_title' => 'Proyecto de prueba',
            'english_title' => 'Test project',
            'is_published' => true,
            'sort_order' => 1,
        ]);
    }
}
