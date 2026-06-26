<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $activeProjectsCount = Project::active()->count();
        $totalViews = Project::sum('total_views');
        $totalDownloads = Project::sum('total_downloads');
        
        $recentProjects = Project::orderBy('created_at', 'desc')->take(5)->get();

        return view('admin.dashboard', compact(
            'activeProjectsCount',
            'totalViews',
            'totalDownloads',
            'recentProjects'
        ));
    }
}
