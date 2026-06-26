@extends('admin.layouts.app')

@section('title', 'Projects')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Projects</h2>
        <p>Manage your client photography projects</p>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>New Project</span>
    </a>
</div>

@if($projects->isEmpty())
    <div class="card" style="text-align: center; padding: 60px 0; color: var(--text-secondary);">
        <p style="margin-bottom: 20px; font-size: 16px;">No projects found.</p>
        <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">Create Your First Project</a>
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
                        <span>{{ $project->galleries->count() }} tabs</span>
                        <span>{{ $project->galleries->sum(fn($g) => $g->photos->count()) }} photos</span>
                    </div>
                    
                    <div style="margin-top: 12px; font-size: 13px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 4px;">
                        <div>Views: <strong>{{ $project->total_views }}</strong></div>
                        <div>Downloads: <strong>{{ $project->total_downloads }}</strong></div>
                    </div>

                    <div class="project-footer">
                        <span>Expires: {{ $project->expires_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
