<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Gallery;
use App\Models\Photo;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetProjectCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_a_processed_project_photo_as_the_cover(): void
    {
        $admin = Admin::create(['email' => 'admin@example.com', 'password' => 'secret']);
        $project = $this->createProject('project-one');
        $gallery = $this->createGallery($project);
        $photo = $this->createPhoto($gallery);

        $response = $this->actingAs($admin)->post(route('admin.projects.photos.hero', [$project, $photo]));

        $response->assertRedirect(route('admin.projects.show', [
            'project' => $project->id,
            'gallery_id' => $gallery->id,
            'tab' => 'gallery',
        ]));
        $this->assertSame($photo->id, $project->fresh()->hero_photo_id);
    }

    public function test_admin_cannot_use_a_photo_from_another_project_as_the_cover(): void
    {
        $admin = Admin::create(['email' => 'admin@example.com', 'password' => 'secret']);
        $project = $this->createProject('project-one');
        $otherProject = $this->createProject('project-two');
        $otherPhoto = $this->createPhoto($this->createGallery($otherProject));

        $this->actingAs($admin)
            ->post(route('admin.projects.photos.hero', [$project, $otherPhoto]))
            ->assertNotFound();

        $this->assertNull($project->fresh()->hero_photo_id);
    }

    private function createProject(string $slug): Project
    {
        return Project::create([
            'title' => 'Test Project',
            'slug' => $slug,
            'expires_at' => now()->addMonth(),
        ]);
    }

    private function createGallery(Project $project): Gallery
    {
        return Gallery::create([
            'project_id' => $project->id,
            'title' => 'Gallery',
            'slug' => 'gallery',
        ]);
    }

    private function createPhoto(Gallery $gallery): Photo
    {
        return Photo::create([
            'gallery_id' => $gallery->id,
            'original_filename' => 'photo.jpg',
            'original_path' => 'originals/photo.jpg',
            'web_path' => 'web/photo.webp',
            'thumbnail_path' => 'thumbnails/photo.webp',
            'is_processed' => true,
        ]);
    }
}
