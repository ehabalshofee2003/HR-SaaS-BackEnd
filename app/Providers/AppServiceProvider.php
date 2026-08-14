<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use App\Repositories\Interfaces\OtpRepositoryInterface;
use App\Repositories\Auth\OtpRepository;
use App\Repositories\Interfaces\UserDeviceRepositoryInterface;
use App\Repositories\Auth\UserDeviceRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OtpRepositoryInterface::class, OtpRepository::class);
        $this->app->bind(UserDeviceRepositoryInterface::class, UserDeviceRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Owner\ManagerRepositoryInterface::class, \App\Repositories\Owner\ManagerRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Owner\DashboardRepositoryInterface::class, \App\Repositories\Owner\DashboardRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Owner\ExceptionRepositoryInterface::class, \App\Repositories\Owner\ExceptionRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Owner\ProfileRepositoryInterface::class, \App\Repositories\Owner\ProfileRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Owner\NotificationRepositoryInterface::class, \App\Repositories\Owner\NotificationRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\SuperAdmin\CompanyRepositoryInterface::class, \App\Repositories\SuperAdmin\CompanyRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Supervisor\EmployeeRepositoryInterface::class, \App\Repositories\Supervisor\EmployeeRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Supervisor\TaskRepositoryInterface::class, \App\Repositories\Supervisor\TaskRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Supervisor\LeaveRepositoryInterface::class, \App\Repositories\Supervisor\LeaveRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Supervisor\TaskManagementRepositoryInterface::class, \App\Repositories\Supervisor\TaskManagementRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\Supervisor\ProfileRepositoryInterface::class, \App\Repositories\Supervisor\ProfileRepository::class);
        
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