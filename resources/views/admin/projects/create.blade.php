@extends('admin.layouts.app')

@section('title', 'Create Project')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Create New Project</h2>
        <p>Set up access and expiration settings for your new project</p>
    </div>
    <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">
        <span>Cancel</span>
    </a>
</div>

<div class="card" x-data="{ isProtected: false }">
    <form action="{{ route('admin.projects.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="title" class="form-label">Project Title</label>
            <input 
                type="text" 
                name="title" 
                id="title" 
                class="form-control @error('title') is-invalid @enderror" 
                placeholder="Client Name or Photoshoot Title" 
                value="{{ old('title') }}" 
                required
            >
            @error('title')
                <span style="color: var(--danger); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="expires_at" class="form-label">Expiration Date</label>
            <input 
                type="date" 
                name="expires_at" 
                id="expires_at" 
                class="form-control @error('expires_at') is-invalid @enderror" 
                value="{{ old('expires_at', now()->addMonth()->format('Y-m-d')) }}" 
                required
            >
            @error('expires_at')
                <span style="color: var(--danger); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group" style="margin-top: 24px;">
            <label class="form-switch">
                <input 
                    type="checkbox" 
                    name="is_password_protected" 
                    value="1" 
                    x-model="isProtected"
                    {{ old('is_password_protected') ? 'checked' : '' }}
                >
                <span class="switch-slider"></span>
                <span class="form-label" style="margin-bottom: 0;">Password Protection</span>
            </label>
        </div>

        <div class="form-group" x-show="isProtected" x-transition style="display: none;">
            <label for="password" class="form-label">Password</label>
            <input 
                type="password" 
                name="password" 
                id="password" 
                class="form-control @error('password') is-invalid @enderror" 
                placeholder="Enter client password"
                ::required="isProtected"
            >
            @error('password')
                <span style="color: var(--danger); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group" style="margin-top: 24px; margin-bottom: 32px;">
            <label class="form-switch">
                <input 
                    type="checkbox" 
                    name="allow_download" 
                    value="1" 
                    checked
                >
                <span class="switch-slider"></span>
                <span class="form-label" style="margin-bottom: 0;">Allow Download (Client can download original photos)</span>
            </label>
        </div>

        <div style="display: flex; gap: 16px; border-top: 1px solid var(--border-color); padding-top: 24px;">
            <button type="submit" class="btn btn-primary">Create Project</button>
            <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </form>
</div>
@endsection
