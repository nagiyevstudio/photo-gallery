@extends('admin.layouts.app')

@section('title', $project->title)

@section('content')
@php
    $activeGalleryId = request('gallery_id') ?: ($project->galleries->first()?->id);
    $activeGallery = $project->galleries->first(fn($g) => $g->id == $activeGalleryId) ?: $project->galleries->first();
@endphp

<div class="page-header">
    <div class="page-title">
        <h2>{{ $project->title }}</h2>
        <p>
            Public link: 
            <a href="{{ route('project.show', $project->slug) }}" target="_blank" style="color: var(--accent); text-decoration: none;">
                {{ route('project.show', $project->slug) }} ↗
            </a>
        </p>
    </div>
    <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">
        <span>Back to Projects</span>
    </a>
</div>

<!-- Tabs Navigation -->
<div class="tabs-navigation">
    <button class="tab-btn active" data-tab="gallery">Gallery Tabs</button>
    <button class="tab-btn" data-tab="upload">Upload Media</button>
    <button class="tab-btn" data-tab="settings">Project Settings</button>
    <button class="tab-btn" data-tab="stats">Statistics</button>
</div>

<!-- --- 1. GALLERY TAB --- -->
<div class="tab-content active" id="gallery-tab">
    @if($project->galleries->isEmpty())
        <div class="card" style="text-align: center; padding: 60px 0; color: var(--text-secondary);">
            <p style="margin-bottom: 20px;">No galleries created yet. Go to the "Upload Media" tab to load photos!</p>
        </div>
    @else
        <div class="gallery-layout">
            <!-- Galleries Sidebar -->
            <div class="gallery-sidebar">
                <div class="gallery-sidebar-title">
                    <span>Galleries</span>
                    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('add-gallery-modal').style.display='flex'">+ Add</button>
                </div>
                
                <div class="tabs-list" id="galleries-list" data-project-id="{{ $project->id }}" data-project-slug="{{ $project->slug }}">
                    @foreach($project->galleries as $gallery)
                        <div class="gallery-tab-item {{ $activeGallery && $activeGallery->id === $gallery->id ? 'active' : '' }}" 
                             data-id="{{ $gallery->id }}"
                             onclick="window.location.href='?gallery_id={{ $gallery->id }}&tab=gallery'"
                        >
                            <span>{{ $gallery->title }} ({{ $gallery->photos->count() }})</span>
                            
                            <form action="{{ route('admin.projects.galleries.destroy', [$project->id, $gallery->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this gallery and all its photos?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size: 11px;">×</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Active Gallery Photo Grid -->
            <div>
                @if($activeGallery)
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                        <h4 style="font-size:18px;">{{ $activeGallery->title }} ({{ $activeGallery->photos->count() }} Photos)</h4>
                        
                        <div style="display:flex; gap:8px;">
                            <input type="file" id="active-gallery-upload-input" multiple accept="image/jpeg,image/png,image/webp" style="display:none;" data-gallery-id="{{ $activeGallery->id }}" data-gallery-title="{{ $activeGallery->title }}">
                            <button type="button" class="btn btn-primary btn-sm" id="btn-active-gallery-upload" style="display:inline-flex; align-items:center; gap:6px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>+ Add Photos</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('rename-gallery-modal-{{ $activeGallery->id }}').style.display='flex'">
                                Rename Gallery
                            </button>
                        </div>
                    </div>

                    @if($activeGallery->photos->isEmpty())
                        <div class="card" style="text-align: center; padding: 40px 0; color: var(--text-secondary);">
                            <p style="margin-bottom: 16px;">No photos in this gallery yet.</p>
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('active-gallery-upload-input').click()">
                                + Add Photos to this Gallery
                            </button>
                        </div>
                    @else
                        <div class="photos-grid" id="photo-grid" data-project-id="{{ $project->id }}">
                            @foreach($activeGallery->photos as $photo)
                                <div class="photo-item {{ $project->hero_photo_id === $photo->id ? 'is-cover' : '' }}" data-id="{{ $photo->id }}">
                                    <img src="{{ $photo->thumbnail_url ?: 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%231a1a1a%22/%3E%3C/svg%3E' }}" alt="Photo">
                                    @if($project->hero_photo_id === $photo->id)
                                        <span class="photo-cover-badge">Cover</span>
                                    @endif
                                    <div class="photo-overlay">
                                        @if($project->hero_photo_id !== $photo->id && $photo->is_processed)
                                            <form action="{{ route('admin.projects.photos.hero', [$project->id, $photo->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="photo-cover-btn">Set as cover</button>
                                            </form>
                                        @else
                                            <span></span>
                                        @endif
                                        <form action="{{ route('admin.projects.photos.destroy', [$project->id, $photo->id]) }}" method="POST" onsubmit="return confirm('Delete this photo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="photo-delete-btn">×</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>

<!-- --- 2. UPLOAD TAB --- -->
<div class="tab-content" id="upload-tab">
    <div class="card">
        <h3 class="card-title">Upload Media</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; font-size:14px;">
            To preserve structure, you can drop folders directly. Each folder will automatically convert to a separate Gallery tab.
        </p>

        <!-- Hidden Inputs for Files/Folders Selection -->
        <input type="file" id="folder-upload-input" webkitdirectory directory multiple style="display:none;">
        <input type="file" id="file-upload-input" multiple accept="image/jpeg,image/png,image/webp" style="display:none;">

        <!-- Target Gallery Selector for loose photos -->
        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 12px 16px; border-radius: 6px;">
            <label for="target-gallery-select" style="font-size: 13px; font-weight: 500; color: var(--text-primary); margin-bottom: 0;">
                📁 Target gallery for individual photos:
            </label>
            <select id="target-gallery-select" class="form-control" style="width: auto; min-width: 220px; padding: 6px 12px; font-size: 13px;">
                <option value="">Auto (Folder name or "Unsorted")</option>
                @foreach($project->galleries as $gallery)
                    <option value="{{ $gallery->id }}" data-gallery-title="{{ $gallery->title }}" {{ $activeGallery && $activeGallery->id === $gallery->id ? 'selected' : '' }}>
                        {{ $gallery->title }} ({{ $gallery->photos->count() }})
                    </option>
                @endforeach
            </select>
            <span style="font-size: 12px; color: var(--text-muted);">
                (Dropped folders will always use their own folder names)
            </span>
        </div>

        <!-- Enhanced Dropzone Area -->
        <div class="upload-dropzone" id="upload-dropzone" data-project-id="{{ $project->id }}">
            <div class="upload-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </div>
            <p style="font-size:17px; font-weight:600; margin-bottom:6px; color: white;">
                Drag & Drop Folders or Photos Here
            </p>
            <p style="font-size:13px; color: var(--text-secondary); max-width: 480px; margin: 0 auto;">
                Drop one or multiple folders directly from your file manager to create galleries automatically, or use the buttons below:
            </p>
            
            <div class="dropzone-actions">
                <button type="button" class="btn btn-primary" id="btn-browse-folders" style="display:inline-flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span>Choose Folders</span>
                </button>
                <button type="button" class="btn btn-secondary" id="btn-browse-files" style="display:inline-flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Choose Photos</span>
                </button>
            </div>

            <div class="dropzone-tip">
                <span>💡 Dragging folders directly from Explorer creates a separate gallery for each folder without security prompts.</span>
            </div>
        </div>

        <!-- Upload Dashboard Container -->
        <div class="upload-dashboard" id="upload-dashboard" style="display:none;">
            <!-- Completion Success Banner -->
            <div class="upload-complete-banner" id="upload-complete-banner" style="display:none;">
                <div class="complete-icon">✓</div>
                <div class="complete-info">
                    <h4 id="complete-title">Upload Completed!</h4>
                    <p id="complete-subtitle">All photos have been processed.</p>
                </div>
                <button type="button" class="btn btn-primary" id="btn-goto-gallery">
                    Go to Gallery Tabs →
                </button>
            </div>

            <!-- Overall Summary Card -->
            <div class="upload-summary-card">
                <div class="summary-header">
                    <div>
                        <span class="summary-title" id="summary-status-text">Uploading files...</span>
                        <span class="summary-counts" id="summary-counts-text">0 / 0 files</span>
                    </div>
                    <span class="summary-percent" id="summary-percent-text">0%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="summary-progress-bar" style="width: 0%;"></div>
                </div>
            </div>

            <!-- Active File Card -->
            <div class="upload-active-card" id="upload-active-card">
                <div class="active-file-header">
                    <div class="active-file-title">
                        <span class="active-spinner" id="active-spinner"></span>
                        <span id="active-filename">Preparing upload...</span>
                    </div>
                    <span class="active-folder-badge" id="active-folder-badge">Gallery</span>
                </div>
                <div class="active-progress-row">
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" id="active-file-progress-bar" style="width: 0%;"></div>
                    </div>
                    <span class="active-percent" id="active-file-percent">0%</span>
                </div>
            </div>

            <!-- Folders Section -->
            <div class="upload-folders-section" id="upload-folders-section">
                <h4 class="upload-section-title">Galleries in this upload</h4>
                <div class="upload-folders-grid" id="folders-progress-list">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- Detailed Files Accordion -->
            <div class="upload-details-accordion">
                <button type="button" class="accordion-toggle" id="toggle-file-details">
                    <span id="details-toggle-text">Show detailed file list (0 files)</span>
                    <span class="toggle-arrow">▼</span>
                </button>
                <div class="accordion-body" id="file-details-body" style="display:none;">
                    <div class="file-details-list" id="progress-list">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- --- 3. SETTINGS TAB --- -->
<div class="tab-content" id="settings-tab" x-data="{ isProtected: {{ $project->is_password_protected ? 'true' : 'false' }} }">
    <div class="card">
        <form id="generate-zip-form" action="{{ route('admin.projects.generate-zip', $project->id) }}" method="POST" style="display: none;">
            @csrf
        </form>
        <form id="delete-zip-form" action="{{ route('admin.projects.zip.destroy', $project->id) }}" method="POST" style="display: none;" onsubmit="return confirm('Are you sure you want to delete the ZIP archive from the server? Clients will not be able to download all photos until it is recompiled.');">
            @csrf
            @method('DELETE')
        </form>
        <h3 class="card-title">Project Configuration</h3>
        
        <form action="{{ route('admin.projects.update', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title" class="form-label">Project Title</label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    class="form-control" 
                    value="{{ old('title', $project->title) }}" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="expires_at" class="form-label">Expiration Date</label>
                <input 
                    type="date" 
                    name="expires_at" 
                    id="expires_at" 
                    class="form-control" 
                    value="{{ old('expires_at', $project->expires_at->format('Y-m-d')) }}" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Project Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="active" {{ $project->status === 'active' ? 'selected' : '' }}>Active (Visible to Client)</option>
                    <option value="archived" {{ $project->status === 'archived' ? 'selected' : '' }}>Archived (Hidden from Client)</option>
                </select>
            </div>

            <div class="form-group" style="margin-top: 24px;">
                <label class="form-switch">
                    <input 
                        type="checkbox" 
                        name="is_password_protected" 
                        value="1" 
                        x-model="isProtected"
                        {{ $project->is_password_protected ? 'checked' : '' }}
                    >
                    <span class="switch-slider"></span>
                    <span class="form-label" style="margin-bottom: 0;">Password Protection</span>
                </label>
            </div>

            <div class="form-group" x-show="isProtected" x-transition style="display: none;">
                <label for="password" class="form-label">Password (Leave blank to keep existing password)</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-control" 
                    placeholder="••••••••"
                >
            </div>

            <div class="form-group" style="margin-top: 24px; margin-bottom: 32px;">
                <label class="form-switch">
                    <input 
                        type="checkbox" 
                        name="allow_download" 
                        value="1" 
                        {{ $project->allow_download ? 'checked' : '' }}
                    >
                    <span class="switch-slider"></span>
                    <span class="form-label" style="margin-bottom: 0;">Allow Download (Client can download original photos)</span>
                </label>
            </div>

            <div class="form-group" style="margin-top: 24px; margin-bottom: 32px; border-top: 1px solid var(--border-color); padding-top: 24px;">
                <label class="form-label">ZIP Archive Management</label>

                @php
                    $zipInfo = $project->getZipInfo();
                    $totalBytes = $project->totalPhotosSize();
                    $isTooLarge = $totalBytes > 2 * 1024 * 1024 * 1024; // 2 GB threshold
                @endphp

                <!-- Current ZIP Status -->
                @if($zipInfo)
                    @if($zipInfo['is_outdated'])
                        <div style="background: rgba(234, 179, 8, 0.08); border: 1px solid rgba(234, 179, 8, 0.35); padding: 16px; border-radius: 6px; margin-bottom: 16px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                                <div>
                                    <p style="font-size:14px; color: #eab308; margin-bottom: 4px; font-weight: 600;">
                                        ⚠️ ZIP Archive is Outdated!
                                    </p>
                                    <p style="font-size:13px; color: var(--text-secondary); margin-bottom: 4px;">
                                        The current archive contains <strong>{{ $zipInfo['files_count'] }}</strong> photos ({{ $zipInfo['formatted_size'] }}), but the project now has <strong>{{ $zipInfo['project_photos_count'] }}</strong> photos.
                                    </p>
                                    <p style="font-size:12px; color: var(--text-muted); margin-bottom: 0;">
                                        Last compiled: {{ $zipInfo['formatted_date'] }}. Clients downloading the archive will not receive the latest photos until it is recompiled.
                                    </p>
                                </div>
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <button type="submit" form="generate-zip-form" class="btn btn-primary btn-sm" style="background: var(--accent); color: #000; border-color: var(--accent); font-weight: 600;">
                                        🔄 Rebuild ZIP (Delete Old)
                                    </button>
                                    <button type="submit" form="delete-zip-form" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: rgba(239,68,68,0.3);">
                                        Delete ZIP
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="background: rgba(34, 197, 94, 0.06); border: 1px solid rgba(34, 197, 94, 0.25); padding: 16px; border-radius: 6px; margin-bottom: 16px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                                <div>
                                    <p style="font-size:14px; color: var(--success, #22c55e); margin-bottom: 4px; font-weight: 600;">
                                        ✓ ZIP Archive is up to date
                                    </p>
                                    <p style="font-size:13px; color: var(--text-secondary); margin-bottom: 4px;">
                                        Contains all <strong>{{ $zipInfo['files_count'] }}</strong> photos ({{ $zipInfo['formatted_size'] }}). Clients can download it immediately.
                                    </p>
                                    <p style="font-size:12px; color: var(--text-muted); margin-bottom: 0;">
                                        Compiled on: {{ $zipInfo['formatted_date'] }}
                                    </p>
                                </div>
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <button type="submit" form="generate-zip-form" class="btn btn-secondary btn-sm" title="Recompile archive from scratch">
                                        🔄 Rebuild ZIP
                                    </button>
                                    <button type="submit" form="delete-zip-form" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: rgba(239,68,68,0.3);">
                                        Delete ZIP
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 16px; border-radius: 6px; margin-bottom: 16px; color: var(--text-secondary);">
                        <p style="font-size:13px; margin-bottom: 0;">
                            No active ZIP archive on the server. Clients will not be able to download all photos as a ZIP until an archive is compiled or uploaded.
                        </p>
                    </div>
                @endif

                <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 16px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 16px; border-radius: 6px;">
                    <p style="font-size:13px; color: var(--text-secondary); margin-bottom: 0;">
                        Total size of original photos: <strong>{{ $project->formattedTotalPhotosSize() }}</strong>
                    </p>

                    @if($isTooLarge)
                        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; border-radius: 6px; color: var(--text-secondary); font-size: 13px; line-height: 1.5;">
                            <p style="color: var(--danger); font-weight: 500; margin-bottom: 6px;">
                                ⚠️ Size exceeds 2 GB threshold
                            </p>
                            To prevent server timeouts, compiling via the browser is disabled. Please package the photos on your computer and upload the ZIP archive directly via FTP to:
                            <br><code style="display: block; margin-top: 8px; background: #000; padding: 6px 10px; border-radius: 4px; color: #fff; font-size:12px;">www/nagiyev_studio/gallery.nagiyev.com/storage/app/zips/{{ $project->id }}.zip</code>
                        </div>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 0;">
                                {{ $zipInfo ? 'You can recompile the ZIP archive directly on the server (deletes the previous archive and packages current photos).' : 'You can compile the ZIP archive directly on the server (packages files without compression, takes only a few seconds).' }}
                            </p>
                            <button type="submit" form="generate-zip-form" class="btn btn-secondary" style="width: fit-content; background: var(--accent); color: #000; border-color: var(--accent); padding: 8px 16px; font-weight: 500;">
                                ⚙ {{ $zipInfo ? 'Recompile ZIP Archive' : 'Compile ZIP on Server' }}
                            </button>
                        </div>
                    @endif

                    <div style="border-top: 1px dashed var(--border-color); padding-top: 16px; display: flex; flex-direction: column; gap: 10px;">
                        <p style="font-size: 13px; color: var(--text-secondary); font-weight: 500; margin-bottom: 0;">
                            Alternative: Upload Pre-made ZIP
                        </p>
                        
                        <input 
                            type="file" 
                            name="zip_file" 
                            id="zip_file" 
                            class="form-control" 
                            accept=".zip"
                            style="max-width: 400px;"
                        >
                        <p style="font-size:12px; color: var(--text-muted); margin-bottom: 0; line-height: 1.4;">
                            💡 <strong>Web limit warning:</strong> For larger files (over 100MB), web upload might fail. In that case, upload the ZIP archive via FTP to the folder above.
                        </p>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 16px; border-top: 1px solid var(--border-color); padding-top: 24px;">
                <button type="submit" class="btn btn-primary">Save Configuration</button>
            </div>
        </form>
    </div>

    <!-- Danger Zone Delete -->
    <div class="card" style="border-color: rgba(239, 68, 68, 0.2); background-color: rgba(239, 68, 68, 0.02);">
        <h3 class="card-title" style="color: var(--danger);">Danger Zone</h3>
        <p style="color: var(--text-secondary); font-size:13px; margin-bottom:20px;">
            Permanently delete this project. All photos, originals, and zip downloads will be deleted from the server disk. This action is irreversible.
        </p>

        <form action="{{ route('admin.projects.destroy', $project->id) }}" method="POST" onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? THIS DELETES ALL PHOTO IMAGES PERMANENTLY.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete Project</button>
        </form>
    </div>
</div>

<!-- --- 4. STATS TAB --- -->
<div class="tab-content" id="stats-tab">
    <div class="stats-info-grid">
        <div class="metric-card">
            <span class="metric-label">Total Unique Visits</span>
            <div class="metric-value">{{ $project->total_views }}</div>
        </div>
        <div class="metric-card">
            <span class="metric-label">Client Media Downloads</span>
            <div class="metric-value">{{ $project->total_downloads }}</div>
        </div>
    </div>

    <!-- Daily Views Chart -->
    <div class="card">
        @php
            $range = $range ?? 10;
            if (!isset($dailyViews)) {
                $dailyViews = [];
                $views = $project->projectViews;
                $maxView = 0;
                $recentViewsCount = 0;
                for ($i = $range - 1; $i >= 0; $i--) {
                    $dayCarbon = now()->subDays($i);
                    $dateStr = $dayCarbon->format('Y-m-d');
                    $label = $dayCarbon->format('M d');
                    $count = $views->filter(function ($pv) use ($dateStr) {
                        if (!$pv->created_at) return false;
                        $d = $pv->created_at instanceof \Carbon\Carbon ? $pv->created_at->format('Y-m-d') : \Carbon\Carbon::parse($pv->created_at)->format('Y-m-d');
                        return $d === $dateStr;
                    })->count();
                    $dailyViews[$label] = $count;
                    if ($count > $maxView) $maxView = $count;
                    $recentViewsCount += $count;
                }
            }
            $maxView = $maxView ?? (max(array_values($dailyViews)) ?: 0);
            $recentViewsCount = $recentViewsCount ?? array_sum($dailyViews);
        @endphp

        <div class="chart-header">
            <div>
                <h3 class="card-title" style="margin-bottom: 4px;">Daily Unique Views (Last {{ $range }} Days)</h3>
                <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">
                    @if($recentViewsCount > 0)
                        <span><strong>{{ $recentViewsCount }}</strong> {{ Str::plural('view', $recentViewsCount) }} recorded in this period</span>
                    @else
                        <span>No views recorded in the last {{ $range }} days</span>
                    @endif
                    @if($project->total_views > $recentViewsCount)
                        <span style="color: var(--text-muted); margin-left: 6px;">({{ $project->total_views }} all-time)</span>
                    @endif
                </p>
            </div>

            <div class="chart-range-selector">
                <a href="?tab=stats&range=10" class="btn btn-sm {{ $range == 10 ? 'btn-primary' : 'btn-secondary' }}">10 Days</a>
                <a href="?tab=stats&range=30" class="btn btn-sm {{ $range == 30 ? 'btn-primary' : 'btn-secondary' }}">30 Days</a>
            </div>
        </div>

        <div class="chart-container">
            @foreach($dailyViews as $dayLabel => $count)
                @php
                    $heightPercent = $maxView > 0 ? round(($count / $maxView) * 100) : 0;
                @endphp
                <div class="chart-bar-wrapper">
                    <!-- Hover Tooltip -->
                    <div class="chart-tooltip">
                        <div class="chart-tooltip-date">{{ $dayLabel }}</div>
                        <div class="chart-tooltip-count"><strong>{{ $count }}</strong> {{ Str::plural('view', $count) }}</div>
                    </div>

                    <!-- Bar Track -->
                    <div class="chart-bar-track">
                        @if($count > 0)
                            <div class="chart-bar" style="height: {{ max($heightPercent, 6) }}%;">
                                <span class="chart-bar-val">{{ $count }}</span>
                            </div>
                        @else
                            <div class="chart-bar is-zero"></div>
                        @endif
                    </div>

                    <!-- X-axis Date Label -->
                    <span class="chart-label">{{ $dayLabel }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- --- MODALS --- -->

<!-- Add Gallery Modal -->
<div id="add-gallery-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:100;">
    <div class="card" style="width:100%; max-width:480px; margin:24px;">
        <h3 class="card-title">Add New Gallery Tab</h3>
        <form action="{{ route('admin.projects.galleries.store', $project->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="new_gallery_title" class="form-label">Gallery Title</label>
                <input type="text" name="title" id="new_gallery_title" class="form-control" required placeholder="e.g. Ceremony, Banquet">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-gallery-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Rename Gallery Modals -->
@if($activeGallery)
<div id="rename-gallery-modal-{{ $activeGallery->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:100;">
    <div class="card" style="width:100%; max-width:480px; margin:24px;">
        <h3 class="card-title">Rename Gallery Tab</h3>
        <form action="{{ route('admin.projects.galleries.update', [$project->id, $activeGallery->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="rename_gallery_title" class="form-label">Gallery Title</label>
                <input type="text" name="title" id="rename_gallery_title" class="form-control" value="{{ $activeGallery->title }}" required>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('rename-gallery-modal-{{ $activeGallery->id }}').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
