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
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h4 style="font-size:18px;">{{ $activeGallery->title }} ({{ $activeGallery->photos->count() }} Photos)</h4>
                        
                        <button class="btn btn-secondary btn-sm" onclick="document.getElementById('rename-gallery-modal-{{ $activeGallery->id }}').style.display='flex'">
                            Rename Gallery
                        </button>
                    </div>

                    @if($activeGallery->photos->isEmpty())
                        <div class="card" style="text-align: center; padding: 40px 0; color: var(--text-secondary);">
                            <p>No photos in this gallery. Drag folders or files in the "Upload Media" tab.</p>
                        </div>
                    @else
                        <div class="photos-grid" id="photo-grid" data-project-id="{{ $project->id }}">
                            @foreach($activeGallery->photos as $photo)
                                <div class="photo-item" data-id="{{ $photo->id }}">
                                    <img src="{{ $photo->thumbnail_url ?: 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%231a1a1a%22/%3E%3C/svg%3E' }}" alt="Photo">
                                    <div class="photo-overlay">
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
        <input type="file" id="file-upload-input" multiple style="display:none;">

        <div class="upload-dropzone" id="upload-dropzone" data-project-id="{{ $project->id }}">
            <div class="upload-icon">↑</div>
            <p style="font-size:16px; font-weight:500; margin-bottom:8px; color: white;">Drag & Drop Folders Here</p>
            <p style="font-size:13px; color: var(--text-secondary);">Or click here to browse files/folders</p>
        </div>

        <!-- Upload Progress list -->
        <div class="upload-progress-container" style="display:none;">
            <h4 style="font-size:14px; margin-bottom:12px;">Uploading Files</h4>
            <div id="progress-list"></div>
        </div>
    </div>
</div>

<!-- --- 3. SETTINGS TAB --- -->
<div class="tab-content" id="settings-tab" x-data="{ isProtected: {{ $project->is_password_protected ? 'true' : 'false' }} }">
    <div class="card">
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

            <div class="form-group" style="margin-top: 24px; margin-bottom: 32px;">
                <label for="zip_file" class="form-label">Upload Originals ZIP Archive (Optional)</label>
                @if(file_exists(storage_path("app/zips/{$project->id}.zip")))
                    <p style="font-size:13px; color: var(--accent); margin-bottom: 8px;">
                        ✓ Currently uploaded: {{ round(filesize(storage_path("app/zips/{$project->id}.zip")) / 1024 / 1024, 2) }} MB ZIP archive.
                    </p>
                @else
                    <p style="font-size:13px; color: var(--text-secondary); margin-bottom: 8px;">
                        No ZIP file uploaded yet. Upload a pre-made ZIP archive of all original high-res photos for client download.
                    </p>
                @endif
                <input 
                    type="file" 
                    name="zip_file" 
                    id="zip_file" 
                    class="form-control" 
                    accept=".zip"
                >
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
        <h3 class="card-title">Daily Unique Views (Last 10 Days)</h3>
        
        @php
            $dailyViews = [];
            for ($i = 9; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $count = $project->projectViews()->whereDate('created_at', $date)->count();
                $dailyViews[now()->subDays($i)->format('M d')] = $count;
            }
            $maxView = max(array_values($dailyViews)) ?: 1;
        @endphp

        <div class="chart-container">
            @foreach($dailyViews as $dayLabel => $count)
                @php
                    $heightPercent = ($count / $maxView) * 80; // Scale to max 80% height of box
                @endphp
                <div class="chart-bar-wrapper">
                    <div class="chart-tooltip">
                        <span class="tooltiptext">{{ $count }} views</span>
                        <div class="chart-bar" style="height: {{ max($heightPercent, 2) }}%;"></div>
                    </div>
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
