<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::orderBy('created_at', 'desc')->get();
        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.projects.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();
        
        // Generate unique slug
        $slug = Str::slug($data['title']);
        $originalSlug = $slug;
        $count = 1;
        while (Project::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $data['slug'] = $slug;

        // Hash password if enabled
        if ($data['is_password_protected'] && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data['is_password_protected'] = false;
            $data['password'] = null;
        }

        $data['expires_at'] = Carbon::parse($data['expires_at'])->endOfDay();

        Project::create($data);

        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $project->load(['galleries.photos', 'projectViews', 'downloadLogs']);
        return view('admin.projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        return redirect()->route('admin.projects.show', [$project->id, 'tab' => 'settings']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $data = $request->validated();

        // Handle password updates
        if ($data['is_password_protected']) {
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                // If it is checked but password is empty, keep existing password if any
                if ($project->password) {
                    unset($data['password']);
                } else {
                    // Force disable if no password exists
                    $data['is_password_protected'] = false;
                    $data['password'] = null;
                }
            }
        } else {
            $data['password'] = null;
        }

        $data['expires_at'] = Carbon::parse($data['expires_at'])->endOfDay();

        $project->update($data);

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Project settings updated successfully!')
            ->with('tab', 'settings');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        // Delete all files physically from storage
        Storage::disk('local')->deleteDirectory('originals/' . $project->id);
        Storage::disk('local')->deleteDirectory('web/' . $project->id);
        Storage::disk('local')->deleteDirectory('thumbnails/' . $project->id);
        
        // Also delete any compiled public web/thumbnail directories if they exist
        Storage::disk('public')->deleteDirectory('web/' . $project->id);
        Storage::disk('public')->deleteDirectory('thumbnails/' . $project->id);

        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully!');
    }
}
