<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Middleware\AdminAuth;

// --- Admin Authentication Routes ---
Route::get('/debug-storage-status', function () {
    $logPath = storage_path('logs/laravel.log');
    
    $tailCustom = function ($filepath, $lines = 100) {
        if (!file_exists($filepath)) return '';
        $f = fopen($filepath, 'rb');
        if ($f === false) return 'Cannot open file';
        
        $buffer = 4096;
        fseek($f, 0, SEEK_END);
        $pos = ftell($f);
        $data = '';
        
        while ($pos > 0 && substr_count($data, "\n") <= $lines) {
            $read = min($pos, $buffer);
            $pos -= $read;
            fseek($f, $pos);
            $data = fread($f, $read) . $data;
        }
        fclose($f);
        return $data;
    };

    $logs = file_exists($logPath) ? $tailCustom($logPath, 150) : 'No log file found.';
    
    $testDirs = [
        storage_path('app'),
        storage_path('app/originals'),
        storage_path('app/public'),
        storage_path('app/public/web'),
        storage_path('app/public/thumbnails'),
    ];
    
    $status = [];
    foreach ($testDirs as $dir) {
        $status[$dir] = [
            'exists' => is_dir($dir),
            'writable' => is_writable($dir),
            'perms' => is_dir($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : null,
        ];
    }
    
    return response()->json([
        'directories' => $status,
        'logs' => explode("\n", $logs)
    ]);
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // --- Protected Admin Panel Routes ---
    Route::middleware(AdminAuth::class)->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        
        // Projects CRUD
        Route::resource('projects', ProjectController::class);
        
        // Galleries CRUD (nested under projects)
        Route::prefix('projects/{project}')->name('projects.')->group(function () {
            Route::post('galleries/sort', [GalleryController::class, 'sort'])->name('galleries.sort');
            Route::resource('galleries', GalleryController::class)->except(['index', 'show']);
            
            // Photos Management
            Route::post('upload', [PhotoController::class, 'upload'])->name('upload');
            Route::post('photos/sort', [PhotoController::class, 'sort'])->name('photos.sort');
            Route::delete('photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
            Route::post('generate-zip', [ProjectController::class, 'generateZip'])->name('generate-zip');
        });
    });
});

// --- Public API Routes ---
Route::prefix('api/projects/{slug}')->middleware([
    \App\Http\Middleware\CheckProjectAccess::class
])->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ProjectController::class, 'show'])
        ->middleware(\App\Http\Middleware\TrackProjectView::class);
        
    Route::post('verify-password', [\App\Http\Controllers\Api\PasswordController::class, 'verify']);
    Route::get('galleries/{gallerySlug}', [\App\Http\Controllers\Api\GalleryController::class, 'show']);
    Route::post('download-all', [\App\Http\Controllers\Api\DownloadController::class, 'requestZip']);
});

Route::prefix('api')->group(function () {
    Route::get('photos/{photo}/download', [\App\Http\Controllers\Api\DownloadController::class, 'downloadSingle'])
        ->name('photo.download')
        ->middleware('signed');
        
    Route::get('downloads/{token}/status', [\App\Http\Controllers\Api\DownloadController::class, 'zipStatus']);
    Route::get('downloads/{token}/file', [\App\Http\Controllers\Api\DownloadController::class, 'downloadZip']);
});

// --- Landing Page Route ---
Route::get('/', function () {
    return view('landing');
});

// --- Public Client SPA Catch-all Route ---
Route::get('/{slug}', function () {
    return view('gallery.app');
})->where('slug', '[a-z0-9][a-z0-9\-]*')->name('project.show');
