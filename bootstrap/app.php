<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'token.cookie' => \App\Http\Middleware\VerifyTokenCookie::class,
            'user.role' => \App\Http\Middleware\UserRole::class,
            'put.requests' => \App\Http\Middleware\HandlePutRequests::class,
            'throttle.login' => \App\Http\Middleware\LoginThrottleMiddleware::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        
        $middleware->group('api-token-priority', [
            'put.requests',
            'token.cookie',
            'bindings',
        ]);
        
        $middleware->group('api-module-priority', [
            'bindings',
        ]);
            
        $middleware->priority([
            \App\Http\Middleware\HandlePutRequests::class,
            \App\Http\Middleware\VerifyTokenCookie::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        
            return response()->json([
                'message' => __tr('Not Found')
            ], 404);
          
            // if ($request->expectsJson()) {
            // }
        
        });
    })->create();

