<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImage;
use App\Models\Gallery;
use App\Models\Photo;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoController extends Controller
{
    /**
     * Upload and store a photo.
     */
    public function upload(Request $request, Project $project)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:102400'], // max 100MB
            'gallery_name' => ['nullable', 'string', 'max:255'],
        ]);

        $photo = null;
        $originalPath = null;

        try {
            $file = $request->file('file');

            // Resolve gallery — use firstOrCreate to prevent race condition
            // when multiple concurrent uploads try to create the same gallery
            $galleryName = $request->input('gallery_name') ?: 'Unsorted';
            $galleryName = trim($galleryName);
            $gallerySlug = Str::slug($galleryName);

            $gallery = $project->galleries()->firstOrCreate(
                ['slug' => $gallerySlug],
                [
                    'title' => $galleryName,
                    'sort_order' => ($project->galleries()->max('sort_order') ?? 0) + 1,
                ]
            );

            // Generate unique filename
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $safeFilename = Str::uuid().'.'.$file->getClientOriginalExtension();

            // Save original file to storage/app/originals/{project_id}/{gallery_id}/
            // Use explicit path to avoid Laravel 11 'local' disk pointing to private/
            $originalRelDir = "originals/{$project->id}/{$gallery->id}";
            $fullDir = storage_path('app/'.$originalRelDir);
            if (! is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }
            $file->move($fullDir, $safeFilename);
            $originalPath = $originalRelDir.'/'.$safeFilename;

            $nextPhotoSortOrder = ($gallery->photos()->max('sort_order') ?? 0) + 1;

            // Create Photo record
            $photo = Photo::create([
                'gallery_id' => $gallery->id,
                'original_filename' => $originalFilename,
                'original_path' => $originalPath,
                'file_size' => $fileSize,
                'sort_order' => $nextPhotoSortOrder,
                'is_processed' => false,
            ]);

            // Dispatch background processing job
            ProcessImage::dispatch($photo->id);

            return response()->json([
                'success' => true,
                'photo_id' => $photo->id,
                'filename' => $originalFilename,
            ]);
        } catch (\Throwable $e) {
            if ($photo) {
                $photo->delete();
            }

            if ($originalPath) {
                $originalFullPath = storage_path('app/'.$originalPath);
                if (file_exists($originalFullPath)) {
                    unlink($originalFullPath);
                }
            }

            \Log::error('Photo upload failed: '.$e->getMessage(), [
                'project_id' => $project->id,
                'file' => $request->file('file')?->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder photos inside gallery.
     */
    public function sort(Request $request, Project $project)
    {
        $photoIds = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:photos,id'],
        ])['order'];

        foreach ($photoIds as $index => $id) {
            Photo::where('id', $id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Set an existing processed photo as the project cover.
     */
    public function setHero(Project $project, Photo $photo)
    {
        abort_unless($photo->gallery?->project_id === $project->id, 404);

        if (! $photo->is_processed || ! $photo->web_path) {
            return redirect()->route('admin.projects.show', [
                'project' => $project->id,
                'gallery_id' => $photo->gallery_id,
                'tab' => 'gallery',
            ])->with('error', 'The photo must finish processing before it can be used as a cover.');
        }

        $project->update(['hero_photo_id' => $photo->id]);

        return redirect()->route('admin.projects.show', [
            'project' => $project->id,
            'gallery_id' => $photo->gallery_id,
            'tab' => 'gallery',
        ])->with('success', 'Project cover updated successfully!');
    }

    /**
     * Delete a photo.
     */
    public function destroy(Project $project, Photo $photo)
    {
        $gallery = $photo->gallery;

        // Delete physical files using explicit paths
        $originalFullPath = storage_path('app/'.$photo->original_path);
        if (file_exists($originalFullPath)) {
            unlink($originalFullPath);
        }

        if ($photo->web_path) {
            Storage::disk('public')->delete($photo->web_path);
        }

        if ($photo->thumbnail_path) {
            Storage::disk('public')->delete($photo->thumbnail_path);
        }

        // If the deleted photo was the hero image, reset project's hero photo
        if ($project->hero_photo_id === $photo->id) {
            $project->update(['hero_photo_id' => null]);

            // Auto assign another photo as hero if available
            $firstGallery = $project->galleries()->orderBy('sort_order')->first();
            if ($firstGallery) {
                $nextPhoto = Photo::where('gallery_id', '!=', $photo->id)
                    ->whereIn('gallery_id', $project->galleries()->pluck('id'))
                    ->orderBy('sort_order')
                    ->first();
                if ($nextPhoto) {
                    $project->update(['hero_photo_id' => $nextPhoto->id]);
                }
            }
        }

        $photo->delete();

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Photo deleted successfully!')
            ->with('tab', 'gallery');
    }
}
