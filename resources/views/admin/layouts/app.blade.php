<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') | Photography Platform</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/admin.css', 'resources/js/admin/app.js'])
</head>
<body>
    <div class="admin-app">
        <!-- Sticky Top Navigation Header -->
        <header class="admin-header">
            <div class="admin-header-container">
                <!-- Left: Logo and Brand -->
                <div class="header-left">
                    <a href="{{ route('admin.dashboard') }}" class="logo-section">
                        <div class="logo-icon">P</div>
                        <span class="logo-text">Nagiyev Photo</span>
                        <span class="admin-badge">Admin</span>
                    </a>
                </div>

                <!-- Center: Navigation Tabs for Desktop -->
                <nav class="header-nav">
                    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ Route::is('admin.dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.projects.index') }}" class="nav-item {{ Route::is('admin.projects.*') ? 'active' : '' }}">
                        <i data-lucide="images"></i>
                        <span>Projects</span>
                    </a>
                </nav>

                <!-- Right: Quick actions and Logout -->
                <div class="header-right">
                    <a href="/" target="_blank" class="header-action-btn" title="View Public Gallery">
                        <i data-lucide="external-link"></i>
                        <span class="action-btn-text">View Site</span>
                    </a>
                    <form action="{{ route('admin.logout') }}" method="POST" class="logout-form">
                        @csrf
                        <button type="submit" class="header-logout-btn" title="Sign out">
                            <i data-lucide="log-out"></i>
                            <span class="action-btn-text">Logout</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mobile Navigation (Segmented Bar) -->
            <div class="header-mobile-nav">
                <a href="{{ route('admin.dashboard') }}" class="mobile-nav-item {{ Route::is('admin.dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.projects.index') }}" class="mobile-nav-item {{ Route::is('admin.projects.*') ? 'active' : '' }}">
                    <i data-lucide="images"></i>
                    <span>Projects</span>
                </a>
            </div>
        </header>

        <!-- Main Content Slot -->
        <main class="content-wrapper">
            @if (session('success'))
                <div class="alert alert-success" style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="check-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
