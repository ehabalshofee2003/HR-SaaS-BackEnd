<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->callAfterResolving('migrator', function ($migrator) {
            $paths = [
                database_path('migrations/Identity'),
                database_path('migrations/Organization'),
                database_path('migrations/SaaS'),
                database_path('migrations/Hr'),
                database_path('migrations/Payroll'),
                database_path('migrations/Support'),
            ];

            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $migrator->path($path);
                }
            }
        });
        $mainPath = database_path('migrations');
        $directories = glob($mainPath . '/*', GLOB_ONLYDIR);
        
        $paths = array_merge([$mainPath], $directories);
        
        $this->loadMigrationsFrom($paths);
    }
}