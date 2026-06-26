<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\ProjectView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackProjectView
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->attributes->get('project');

        if ($project) {
            $ip = $request->ip();
            $today = now()->startOfDay();

            // Check if this IP viewed this project today
            $alreadyViewed = ProjectView::where('project_id', $project->id)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', $today)
                ->exists();

            if (!$alreadyViewed) {
                ProjectView::create([
                    'project_id' => $project->id,
                    'ip_address' => $ip,
                    'user_agent' => substr($request->userAgent(), 0, 500),
                    'created_at' => now(),
                ]);

                // Increment total views counter on the project
                $project->increment('total_views');
            }
        }

        return $next($request);
    }
}
