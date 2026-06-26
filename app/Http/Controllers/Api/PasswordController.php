<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Verify the project password.
     */
    public function verify(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = $request->attributes->get('project');

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (Hash::check($request->input('password'), $project->password)) {
            $cookieName = 'project_access_' . $project->id;
            $token = md5($project->id . $project->password);
            
            // Set cookie for 7 days (10080 minutes)
            return response()->json(['success' => true])
                ->cookie($cookieName, $token, 10080, '/', null, true, true);
        }

        return response()->json([
            'success' => false,
            'message' => 'Incorrect password'
        ], 422);
    }
}
