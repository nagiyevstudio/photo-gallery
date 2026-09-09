@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Dashboard</h2>
        <p>Overview of your photography platform</p>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
        <span>New Project</span>
    </a>
</div>

<!-- Metrics Overview -->
<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-label" style="display: flex; align-items: center; gap: 6px;">
            <i data-lucide="images" style="width: 14px; height: 14px;"></i>
            Active Projects
        </span>
        <div class="metric-value">{{ $activeProjectsCount }}</div>
    </div>
    
    <div class="metric-card">
        <span class="metric-label" style="display: flex; align-items: center; gap: 6px;">
            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
            Total Unique Views
        </span>
        <div class="metric-value">{{ $totalViews }}</div>
    </div>
    
    <div class="metric-card">
        <span class="metric-label" style="display: flex; align-items: center; gap: 6px;">
            <i data-lucide="download" style="width: 14px; height: 14px;"></i>
            Client Downloads
        </span>
        <div class="metric-value">{{ $totalDownloads }}</div>
    </div>
</div>

<!-- Recent Projects -->
<div class="card">
    <h3 class="card-title">Recent Projects</h3>
    @if($recentProjects->isEmpty())
        <div style="text-align: center; padding: 40px 0; color: var(--text-secondary);">
            <p>No projects found. Create your first project to start uploading photos!</p>
        </div>
    @else
        <div class="project-grid">
            @foreach($recentProjects as $project)
                <div class="project-card">
                    <div class="project-hero" style="background-image: url('{{ $project->hero_image_url ?: 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%231a1a1a%22/%3E%3C/svg%3E' }}')">
                        <span class="status-badge {{ $project->status === 'active' ? 'status-active' : 'status-archived' }}">
                            {{ $project->status }}
                        </span>
                    </div>
                    <div class="project-body">
                        <a href="{{ route('admin.projects.show', $project->id) }}" class="project-card-title">
                            {{ $project->title }}
                        </a>
                        <div class="project-meta-info">
                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="folder" style="width: 12px; height: 12px;"></i>
                                {{ $project->galleries->count() }} tabs
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="images" style="width: 12px; height: 12px;"></i>
                                {{ $project->galleries->sum(fn($g) => $g->photos->count()) }} photos
                            </span>
                        </div>
                        <div class="project-footer">
                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="calendar" style="width: 12px; height: 12px;"></i>
                                Expires: {{ $project->expires_at->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
