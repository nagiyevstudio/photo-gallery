<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display the specified project.
     */
    public function show(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = $request->attributes->get('project');

        $project->load(['galleries.photos']);

        $galleriesData = $project->galleries->map(function ($gallery) {
            return [
                'title' => $gallery->title,
                'slug' => $gallery->slug,
                'photo_count' => $gallery->photos->count(),
            ];
        });

        $zipPath = storage_path("app/zips/{$project->id}.zip");
        $zipSizeFormatted = null;
        if (file_exists($zipPath)) {
            $bytes = filesize($zipPath);
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
            $decimals = ($i >= 3) ? 1 : 0;
            $zipSizeFormatted = round($bytes / pow(1024, max($i, 0)), $decimals) . ' ' . ($units[$i] ?? 'MB');
        } elseif ($project->totalPhotosSize() > 0) {
            $zipSizeFormatted = $project->formattedTotalPhotosSize();
        }

        return response()->json([
            'project' => [
                'title' => $project->title,
                'slug' => $project->slug,
                'hero_image_url' => $project->hero_image_url,
                'allow_download' => $project->allow_download,
                'zip_size_formatted' => $zipSizeFormatted,
                'expires_at' => $project->expires_at->toIso8601String(),
                'expires_at_formatted' => $project->expires_at->format('d.m.Y'),
                'galleries' => $galleriesData,
            ]
        ]);
    }
}
