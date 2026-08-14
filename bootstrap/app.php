<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // مسارات المصادقة الرئيسية (Auth / Public Routes)
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // مسارات الأدوار المختلفة (المحمية بـ auth:sanctum)
            Route::prefix('api/v1/employees')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('routes/api/employee.php'));

            Route::prefix('api/v1/supervisor')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('routes/api/supervisor.php'));

            Route::prefix('api/v1/super-admin')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('routes/api/superadmin.php'));

            Route::prefix('api/v1/owner')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('routes/api/owner.php'));

            Route::prefix('api/v1/branch-manager')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('routes/api/branchmanager.php'));
            Route::prefix('api/auth')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('routes/api/auth.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode() ?: 500,
                ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
            }
        });
    })->create();