<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    /**
     * Display the photos of the specified gallery.
     */
    public function show(Request $request, string $slug, string $gallerySlug)
    {
        /** @var \App\Models\Project $project */
        $project = $request->attributes->get('project');

        $gallery = $project->galleries()->where('slug', $gallerySlug)->first();

        if (!$gallery) {
            return response()->json(['error' => 'Gallery not found.'], 404);
        }

        $gallery->load('photos');

        $photosData = $gallery->photos->map(function ($photo) use ($project) {
            return [
                'id' => $photo->id,
                'thumbnail_url' => $photo->thumbnail_url,
                'web_url' => $photo->web_url,
                'width' => $photo->width,
                'height' => $photo->height,
                'download_url' => $project->allow_download ? $photo->download_url : null,
            ];
        });

        return response()->json([
            'gallery' => [
                'title' => $gallery->title,
                'slug' => $gallery->slug,
                'photos' => $photosData,
            ]
        ]);
    }
}
