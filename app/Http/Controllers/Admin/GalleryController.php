<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * Store a newly created gallery in storage.
     */
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $slug = Str::slug($data['title']);
        $originalSlug = $slug;
        $count = 1;
        while ($project->galleries()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $nextSortOrder = $project->galleries()->max('sort_order') + 1;

        $project->galleries()->create([
            'title' => $data['title'],
            'slug' => $slug,
            'sort_order' => $nextSortOrder,
        ]);

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Gallery created successfully!')
            ->with('tab', 'gallery');
    }

    /**
     * Update the specified gallery in storage.
     */
    public function update(Request $request, Project $project, Gallery $gallery)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $slug = Str::slug($data['title']);
        $originalSlug = $slug;
        $count = 1;
        while ($project->galleries()->where('slug', $slug)->where('id', '!=', $gallery->id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $gallery->update([
            'title' => $data['title'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Gallery updated successfully!')
            ->with('tab', 'gallery');
    }

    /**
     * Remove the specified gallery from storage.
     */
    public function destroy(Project $project, Gallery $gallery)
    {
        // Delete all photos files physically from storage
        Storage::disk('local')->deleteDirectory('originals/' . $project->id . '/' . $gallery->id);
        Storage::disk('local')->deleteDirectory('web/' . $project->id . '/' . $gallery->id);
        Storage::disk('local')->deleteDirectory('thumbnails/' . $project->id . '/' . $gallery->id);
        
        Storage::disk('public')->deleteDirectory('web/' . $project->id . '/' . $gallery->id);
        Storage::disk('public')->deleteDirectory('thumbnails/' . $project->id . '/' . $gallery->id);

        $gallery->delete();

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Gallery deleted successfully!')
            ->with('tab', 'gallery');
    }

    /**
     * Sort galleries order.
     */
    public function sort(Request $request, Project $project)
    {
        $galleryIds = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:galleries,id'],
        ])['order'];

        foreach ($galleryIds as $index => $id) {
            Gallery::where('id', $id)
                ->where('project_id', $project->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
