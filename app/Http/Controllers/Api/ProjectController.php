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

        return response()->json([
            'project' => [
                'title' => $project->title,
                'slug' => $project->slug,
                'hero_image_url' => $project->hero_image_url,
                'allow_download' => $project->allow_download,
                'expires_at' => $project->expires_at->toIso8601String(),
                'expires_at_formatted' => $project->expires_at->format('F d, Y'),
                'galleries' => $galleriesData,
            ]
        ]);
    }
}
