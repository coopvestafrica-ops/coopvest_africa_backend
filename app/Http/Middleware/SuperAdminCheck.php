<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AdminUser;
use App\Models\AdminRole;

class SuperAdminCheck
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Check if user is a super admin
        $adminUser = AdminUser::where('user_id', $user->id)
            ->with('role')
            ->first();

        if (!$adminUser || $adminUser->role->level !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Super admin access required',
            ], 403);
        }

        if ($adminUser->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Admin account is not active',
            ], 403);
        }

        return $next($request);
    }
}
