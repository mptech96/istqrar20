<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => __tr('This user is not authorized')], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json(['status' => false, 'message' => __tr('The user must be of the following role: ' . implode(',', $roles))], 401);
        }

        return $next($request);
    }
}
