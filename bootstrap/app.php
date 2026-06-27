<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route; // <-- مهم جداً: إضافة هذا السطر

return Application::configure(basePath: dirname(__DIR__))
     ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // تحميل ملفات الـ API المفصولة لكل ممثل (Actor)
            Route::prefix('api/v1/employees')
                ->middleware('api')
                ->group(base_path('routes/api/employee.php'));

            Route::prefix('api/v1/supervisor')
                ->middleware('api')
                ->group(base_path('routes/api/supervisor.php'));

            Route::prefix('api/v1/super-admin')
                ->middleware('api')
                ->group(base_path('routes/api/superadmin.php'));
                
            Route::prefix('api/v1/owner')
                ->middleware('api')
                ->group(base_path('routes/api/owner.php'));

            Route::prefix('api/v1/branch-manager')
                ->middleware('api')
                ->group(base_path('routes/api/branchmanager.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        
        
  
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling for API
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