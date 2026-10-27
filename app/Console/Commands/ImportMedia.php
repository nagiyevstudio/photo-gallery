<?php

namespace App\Console\Commands;

use App\Models\Gallery;
use App\Models\Photo;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gallery:import-media 
                            {project : Project ID or Slug} 
                            {gallery : Gallery Name, Slug, or ID} 
                            {file : Absolute or relative path to media file on server} 
                            {--thumb= : Path to generated thumbnail/poster file} 
                            {--width=0 : Media width in pixels} 
                            {--height=0 : Media height in pixels} 
                            {--filename= : Original human-readable filename} 
                            {--cleanup : Remove source temp files after successful import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import large media (video or photo) directly into a project gallery';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectIdentifier = $this->argument('project');
        $project = is_numeric($projectIdentifier)
            ? Project::find($projectIdentifier)
            : Project::where('slug', $projectIdentifier)->first();

        if (!$project) {
            $this->error("Project '{$projectIdentifier}' not found.");
            return Command::FAILURE;
        }

        $galleryIdentifier = $this->argument('gallery');
        $gallery = null;

        if (is_numeric($galleryIdentifier)) {
            $gallery = $project->galleries()->find($galleryIdentifier);
        }

        if (!$gallery) {
            $galleryName = trim($galleryIdentifier);
            $gallerySlug = Str::slug($galleryName);

            $gallery = $project->galleries()->firstOrCreate(
                ['slug' => $gallerySlug],
                [
                    'title' => $galleryName,
                    'sort_order' => ($project->galleries()->max('sort_order') ?? 0) + 1,
                ]
            );
        }

        $sourceFile = $this->argument('file');
        if (!File::exists($sourceFile)) {
            $this->error("Source media file not found at: {$sourceFile}");
            return Command::FAILURE;
        }

        $originalFilename = $this->option('filename') ?: basename($sourceFile);
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION) ?: pathinfo($sourceFile, PATHINFO_EXTENSION));
        $uuid = (string) Str::uuid();
        $safeFilename = "{$uuid}.{$ext}";

        // 1. Copy original file to storage/app/originals/{project_id}/{gallery_id}/
        $originalRelDir = "originals/{$project->id}/{$gallery->id}";
        $originalFullDir = storage_path("app/{$originalRelDir}");
        File::ensureDirectoryExists($originalFullDir);
        $originalFullPath = "{$originalFullDir}/{$safeFilename}";

        File::copy($sourceFile, $originalFullPath);
        $originalRelPath = "{$originalRelDir}/{$safeFilename}";

        $fileSize = filesize($originalFullPath);
        $width = (int) $this->option('width');
        $height = (int) $this->option('height');

        $publicDisk = Storage::disk('public');
        $webRelDir = "web/{$project->id}/{$gallery->id}";
        $thumbRelDir = "thumbnails/{$project->id}/{$gallery->id}";
        $publicDisk->makeDirectory($webRelDir);
        $publicDisk->makeDirectory($thumbRelDir);

        $isVideo = in_array($ext, ['mp4', 'webm', 'mov']);

        // 2. Put file into public web directory for streaming/viewing
        $webFullPath = $publicDisk->path("{$webRelDir}/{$safeFilename}");
        File::copy($originalFullPath, $webFullPath);
        $webRelPath = "{$webRelDir}/{$safeFilename}";

        // 3. Handle thumbnail/poster image
        $thumbRelPath = null;
        $thumbSource = $this->option('thumb');

        if ($thumbSource && File::exists($thumbSource)) {
            $thumbExt = strtolower(pathinfo($thumbSource, PATHINFO_EXTENSION) ?: 'jpg');
            $thumbFilename = "{$uuid}.{$thumbExt}";
            $thumbFullPath = $publicDisk->path("{$thumbRelDir}/{$thumbFilename}");
            File::copy($thumbSource, $thumbFullPath);
            $thumbRelPath = "{$thumbRelDir}/{$thumbFilename}";
        } elseif (!$isVideo) {
            // For images without explicit thumb, thumbnail can point to web path
            $thumbRelPath = $webRelPath;
        }

        // 4. Create Photo model record
        $nextSortOrder = ($gallery->photos()->max('sort_order') ?? 0) + 1;
        $photo = Photo::create([
            'gallery_id' => $gallery->id,
            'original_filename' => $originalFilename,
            'original_path' => $originalRelPath,
            'web_path' => $webRelPath,
            'thumbnail_path' => $thumbRelPath,
            'width' => $width,
            'height' => $height,
            'file_size' => $fileSize,
            'sort_order' => $nextSortOrder,
            'is_processed' => true,
        ]);

        // 5. Invalidate existing project ZIP caches so new download includes this file
        $cachedZipPath = storage_path("app/zips/{$project->id}.zip");
        if (File::exists($cachedZipPath)) {
            File::delete($cachedZipPath);
        }

        // 6. Cleanup temp source files if requested
        if ($this->option('cleanup')) {
            File::delete($sourceFile);
            if ($thumbSource && File::exists($thumbSource)) {
                File::delete($thumbSource);
            }
        }

        $result = [
            'success' => true,
            'photo_id' => $photo->id,
            'project_slug' => $project->slug,
            'gallery_title' => $gallery->title,
            'filename' => $originalFilename,
            'is_video' => $photo->is_video,
            'width' => $photo->width,
            'height' => $photo->height,
            'web_url' => $photo->web_url,
            'thumbnail_url' => $photo->thumbnail_url,
        ];

        $this->line(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
