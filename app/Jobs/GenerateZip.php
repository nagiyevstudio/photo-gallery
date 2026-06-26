<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class GenerateZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $projectId;
    protected $token;

    /**
     * Create a new job instance.
     */
    public function __construct($projectId, $token)
    {
        $this->projectId = $projectId;
        $this->token = $token;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::find($this->projectId);
        if (!$project) {
            Cache::put("zip:{$this->token}", ['status' => 'error', 'message' => 'Project not found.'], 7200);
            return;
        }

        // Ensure temporary zips folder exists
        Storage::makeDirectory('zips');

        $zipFileName = "zips/{$this->token}.zip";
        $zipFullPath = storage_path("app/{$zipFileName}");

        $zip = new ZipArchive();

        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error("GenerateZip Job: Failed to open/create ZIP file at {$zipFullPath}");
            Cache::put("zip:{$this->token}", ['status' => 'error', 'message' => 'Failed to initialize ZIP archive.'], 7200);
            return;
        }

        $project->load('galleries.photos');
        $photoAddedCount = 0;

        foreach ($project->galleries as $gallery) {
            // Clean gallery title to prevent folder name issues inside ZIP
            $galleryDir = Str::ascii($gallery->title);
            $galleryDir = preg_replace('/[^A-Za-z0-9 _-]/', '', $galleryDir);
            $galleryDir = trim($galleryDir);

            foreach ($gallery->photos as $photo) {
                $photoFullPath = storage_path('app/' . $photo->original_path);
                
                if (file_exists($photoFullPath)) {
                    // Place inside folder named after the gallery
                    $zipPath = "{$galleryDir}/{$photo->original_filename}";
                    
                    // Add to ZIP (store compression, preserves exact originals 1:1, super fast)
                    $zip->addFile($photoFullPath, $zipPath);
                    $zip->setCompressionName($zipPath, ZipArchive::CM_STORE);
                    
                    $photoAddedCount++;
                }
            }
        }

        $zip->close();

        if ($photoAddedCount === 0) {
            // No photos added, delete empty zip
            if (file_exists($zipFullPath)) {
                unlink($zipFullPath);
            }
            Log::warning("GenerateZip Job: Completed but ZIP contains 0 photos for project {$this->projectId}");
            Cache::put("zip:{$this->token}", ['status' => 'error', 'message' => 'The project contains no valid original images to package.'], 7200);
            return;
        }

        // Update cache to ready status, include file size and project ID
        $fileSize = filesize($zipFullPath);
        Cache::put("zip:{$this->token}", [
            'status' => 'ready',
            'project_id' => $project->id,
            'size' => $fileSize,
        ], 7200);

        Log::info("GenerateZip Job: Successfully generated ZIP for project {$project->id} with {$photoAddedCount} files. Size: {$fileSize} bytes.");
    }
}
