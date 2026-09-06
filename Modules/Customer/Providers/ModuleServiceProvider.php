<?php

namespace App\Modules\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\CRM\CustomerRepositoryInterface;
use App\Repositories\CRM\CustomerRepository;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}
