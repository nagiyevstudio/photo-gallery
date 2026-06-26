<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        $project = Project::where('slug', $slug)->first();

        if (!$project) {
            return response()->json(['error' => 'Project not found.'], 404);
        }

        // 1. Expiration & Status Check
        if ($project->status === 'archived' || $project->isExpired()) {
            return response()->json([
                'error' => 'expired',
                'message' => 'This gallery is no longer available.'
            ], 410);
        }

        // 2. Password Gate Check
        if ($project->is_password_protected) {
            $cookieName = 'project_access_' . $project->id;
            $token = $request->cookie($cookieName);
            $expectedToken = md5($project->id . $project->password);

            if (!$token || $token !== $expectedToken) {
                return response()->json(['requires_password' => true], 401);
            }
        }

        // Attach project to the request so we don't have to query it again in the controller
        $request->attributes->set('project', $project);

        return $next($request);
    }
}
