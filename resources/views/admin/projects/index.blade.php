@extends('admin.layouts.app')

@section('title', 'Projects')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Projects</h2>
        <p>Manage your client photography projects</p>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
        <span>New Project</span>
    </a>
</div>

@if($projects->isEmpty())
    <div class="card" style="text-align: center; padding: 60px 0; color: var(--text-secondary);">
        <p style="margin-bottom: 20px; font-size: 16px;">No projects found.</p>
        <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
            <span>Create Your First Project</span>
        </a>
    </div>
@else
    <div class="project-grid">
        @foreach($projects as $project)
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
                    
                    <div style="margin-top: 12px; font-size: 13px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 6px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                            <span>Views: <strong>{{ $project->total_views }}</strong></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="download" style="width: 13px; height: 13px;"></i>
                            <span>Downloads: <strong>{{ $project->total_downloads }}</strong></span>
                        </div>
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
@endsection
