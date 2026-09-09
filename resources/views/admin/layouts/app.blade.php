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
    <div class="layout-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo-section">
                <div class="logo-icon">P</div>
                <span class="logo-text">Nagiyev Photo</span>
            </div>

            <ul class="nav-menu">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ Route::is('admin.dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.projects.index') }}" class="nav-link {{ Route::is('admin.projects.*') ? 'active' : '' }}">
                        <i data-lucide="images" style="width: 18px; height: 18px;"></i>
                        <span>Projects</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link" style="background:none; border:none; width:100%; text-align:left; cursor:pointer;">
                        <i data-lucide="log-out" style="width: 18px; height: 18px;"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

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
