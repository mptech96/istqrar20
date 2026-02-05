<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;


class VerifyTokenCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('auth_token');
        
        if ($token) {
            
            $accessToken = PersonalAccessToken::findToken(decrypt($token));
            
            if ($accessToken) {
                
                $user = $accessToken->tokenable;
                Auth::setUser($user);
                return $next($request);
                
            }
        }
        
        return response()->json(['error' => 'Authentication required'], 401);
    }
}
