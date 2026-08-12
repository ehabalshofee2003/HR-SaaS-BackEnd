<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user || !$user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للقيام بهذا الإجراء.',
                'code' => 403,
            ], 403);
        }

        return $next($request);
    }
}