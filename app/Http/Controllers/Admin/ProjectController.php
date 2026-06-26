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

        if ($request->hasFile('zip_file')) {
            $zipFile = $request->file('zip_file');
            Storage::makeDirectory('zips');
            $zipFile->storeAs('zips', "{$project->id}.zip");
        }

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
        Storage::disk('local')->delete('zips/' . $project->id . '.zip');
        
        // Also delete any compiled public web/thumbnail directories if they exist
        Storage::disk('public')->deleteDirectory('web/' . $project->id);
        Storage::disk('public')->deleteDirectory('thumbnails/' . $project->id);

        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully!');
    }

    /**
     * Generate ZIP archive synchronously.
     */
    public function generateZip(Project $project)
    {
        $maxBytes = 2 * 1024 * 1024 * 1024; // 2 GB
        $totalSize = $project->totalPhotosSize();

        if ($totalSize > $maxBytes) {
            return redirect()->back()
                ->with('error', 'The project is too large (' . $project->formattedTotalPhotosSize() . ') to generate a ZIP via browser. Please upload the ZIP archive directly via FTP.')
                ->with('tab', 'settings');
        }

        set_time_limit(300); // 5 minutes max

        $zipDir = storage_path('app/zips');
        if (!file_exists($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zipPath = $zipDir . '/' . $project->id . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()
                ->with('error', 'Failed to initialize ZIP archive.')
                ->with('tab', 'settings');
        }

        $project->load('galleries.photos');
        $photoAddedCount = 0;

        foreach ($project->galleries as $gallery) {
            $galleryDir = Str::ascii($gallery->title);
            $galleryDir = preg_replace('/[^A-Za-z0-9 _-]/', '', $galleryDir);
            $galleryDir = trim($galleryDir);

            foreach ($gallery->photos as $photo) {
                $photoFullPath = storage_path('app/' . $photo->original_path);
                if (file_exists($photoFullPath)) {
                    $zipPathInArchive = "{$galleryDir}/{$photo->original_filename}";
                    $zip->addFile($photoFullPath, $zipPathInArchive);
                    $zip->setCompressionName($zipPathInArchive, \ZipArchive::CM_STORE);
                    $photoAddedCount++;
                }
            }
        }

        $zip->close();

        if ($photoAddedCount === 0) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            return redirect()->back()
                ->with('error', 'The project contains no valid original images to package.')
                ->with('tab', 'settings');
        }

        return redirect()->route('admin.projects.show', [$project->id, 'tab' => 'settings'])
            ->with('success', 'ZIP archive compiled successfully! (' . round(filesize($zipPath) / 1024 / 1024, 2) . ' MB)')
            ->with('tab', 'settings');
    }
}
