<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\CheckProjectAccess;
use App\Http\Middleware\TrackProjectView;
use Illuminate\Support\Facades\Route;

// --- Admin Authentication Routes ---
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
            Route::post('photos/{photo}/hero', [PhotoController::class, 'setHero'])->name('photos.hero');
            Route::delete('photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
            Route::post('generate-zip', [ProjectController::class, 'generateZip'])->name('generate-zip');
        });
    });
});

// --- Public API Routes ---
Route::prefix('api/projects/{slug}')->middleware([
    CheckProjectAccess::class,
])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ProjectController::class, 'show'])
        ->middleware(TrackProjectView::class);

    Route::post('verify-password', [PasswordController::class, 'verify']);
    Route::get('galleries/{gallerySlug}', [App\Http\Controllers\Api\GalleryController::class, 'show']);
    Route::post('download-all', [DownloadController::class, 'requestZip']);
});

Route::prefix('api')->group(function () {
    Route::get('photos/{photo}/download', [DownloadController::class, 'downloadSingle'])
        ->name('photo.download')
        ->middleware('signed');

    Route::get('downloads/{token}/status', [DownloadController::class, 'zipStatus']);
    Route::get('downloads/{token}/file', [DownloadController::class, 'downloadZip']);
});

// --- Landing Page Route ---
Route::get('/', function () {
    return view('landing');
});

// --- Public Client SPA Catch-all Route ---
Route::get('/{slug}', function () {
    return view('gallery.app');
})->where('slug', '[a-z0-9][a-z0-9\-]*')->name('project.show');
