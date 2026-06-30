<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Models\Project;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProcessImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $photoId;

    /**
     * Create a new job instance.
     */
    public function __construct($photoId)
    {
        $this->photoId = $photoId;
    }

    /**
     * Execute the job.
     */
    public function handle(ImageService $imageService): void
    {
        $photo = Photo::find($this->photoId);
        if (!$photo) {
            return;
        }

        $gallery = $photo->gallery;
        $project = $gallery->project;

        $originalFullPath = storage_path('app/' . $photo->original_path);

        if (!File::exists($originalFullPath)) {
            Log::error("ProcessImage Job: Original file not found at " . $originalFullPath);
            return;
        }

        // Directories inside storage/app/public/ (accessible via /storage symlink)
        $webRelDir = "web/{$project->id}/{$gallery->id}";
        $thumbRelDir = "thumbnails/{$project->id}/{$gallery->id}";

        // Create directories using absolute paths to avoid Laravel 11 'local' disk mismatch
        $webFullDir = storage_path("app/public/{$webRelDir}");
        $thumbFullDir = storage_path("app/public/{$thumbRelDir}");

        if (!is_dir($webFullDir)) {
            mkdir($webFullDir, 0755, true);
        }
        if (!is_dir($thumbFullDir)) {
            mkdir($thumbFullDir, 0755, true);
        }

        $filenameWebp = File::name($photo->original_filename) . '.webp';
        
        $webFullPath = "{$webFullDir}/{$filenameWebp}";
        $thumbFullPath = "{$thumbFullDir}/{$filenameWebp}";

        try {
            // 1. Create Web Version
            $webDetails = $imageService->createWebVersion($originalFullPath, $webFullPath);

            // 2. Create Thumbnail
            $imageService->createThumbnail($originalFullPath, $thumbFullPath);

            // 3. Update photo record
            $photo->update([
                'web_path' => "web/{$project->id}/{$gallery->id}/{$filenameWebp}",
                'thumbnail_path' => "thumbnails/{$project->id}/{$gallery->id}/{$filenameWebp}",
                'width' => $webDetails['width'],
                'height' => $webDetails['height'],
                'is_processed' => true,
            ]);

            // 4. Hero image auto-assignment
            // Check if project has no hero photo
            if (is_null($project->hero_photo_id)) {
                // If it is the first photo of the first gallery
                $firstGallery = $project->galleries()->orderBy('sort_order')->first();
                if ($firstGallery && $firstGallery->id === $gallery->id) {
                    $firstPhoto = $firstGallery->photos()->orderBy('sort_order')->first();
                    if ($firstPhoto && $firstPhoto->id === $photo->id) {
                        $project->update(['hero_photo_id' => $photo->id]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("ProcessImage Job failed for photo {$photo->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
