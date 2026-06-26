<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Project;
use App\Models\DownloadLog;
use App\Jobs\GenerateZip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    /**
     * Download a single original photo.
     */
    public function downloadSingle(Request $request, Photo $photo)
    {
        $gallery = $photo->gallery;
        $project = $gallery->project;

        // Verify downloads are allowed and project is active
        if (!$project->allow_download || $project->status !== 'active' || $project->isExpired()) {
            abort(403, 'Downloading is disabled for this project.');
        }

        $fullPath = storage_path('app/' . $photo->original_path);

        if (!file_exists($fullPath)) {
            abort(404, 'File not found on server.');
        }

        // Log the download
        DownloadLog::create([
            'project_id' => $project->id,
            'photo_id' => $photo->id,
            'type' => 'single',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $project->increment('total_downloads');

        return response()->download($fullPath, $photo->original_filename);
    }

    /**
     * Start background ZIP generation for all original photos in project.
     */
    public function requestZip(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = $request->attributes->get('project');

        if (!$project->allow_download) {
            return response()->json(['error' => 'Downloading is disabled for this project.'], 403);
        }

        $token = Str::uuid()->toString();

        // Put generating status in cache (lasts for 2 hours)
        Cache::put("zip:{$token}", ['status' => 'generating'], 7200);

        // Dispatch background ZIP generation
        GenerateZip::dispatch($project->id, $token);

        return response()->json([
            'token' => $token,
            'message' => 'ZIP generation started'
        ], 202);
    }

    /**
     * Check ZIP generation status.
     */
    public function zipStatus(string $token)
    {
        $status = Cache::get("zip:{$token}");

        if (!$status) {
            return response()->json(['status' => 'error', 'message' => 'ZIP request expired or invalid.'], 404);
        }

        return response()->json($status);
    }

    /**
     * Download the completed ZIP file.
     */
    public function downloadZip(Request $request, string $token)
    {
        $status = Cache::get("zip:{$token}");

        if (!$status || $status['status'] !== 'ready') {
            abort(404, 'ZIP archive is not ready or has expired.');
        }

        $zipPath = storage_path("app/zips/{$token}.zip");

        if (!file_exists($zipPath)) {
            abort(404, 'ZIP archive file not found.');
        }

        $project = Project::find($status['project_id']);
        if ($project) {
            // Log download
            DownloadLog::create([
                'project_id' => $project->id,
                'photo_id' => null,
                'type' => 'zip',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            $project->increment('total_downloads');
            $filename = Str::slug($project->title) . '-photos.zip';
        } else {
            $filename = 'photos.zip';
        }

        return response()->download($zipPath, $filename);
    }
}
