<?php

namespace App\Modules\Distribution\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Sales\SalesmanRepositoryInterface;
use App\Repositories\Sales\SalesmanRepository;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesmanRepositoryInterface::class, SalesmanRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}
