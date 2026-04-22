<?php

namespace App\Providers;

use App\Repositories\Base\ModelRepository;
use App\Repositories\Contracts\HealthCenterRepositoryInterface;
use App\Repositories\Contracts\VaccineRepositoryInterface;
use App\Repositories\Contracts\VaccineStockRepositoryInterface;
use App\Repositories\Contracts\VaccineScheduleRepositoryInterface;
use App\Repositories\HealthCenterRepository;
use App\Repositories\VaccineRepository;
use App\Repositories\VaccineStockRepository;
use App\Repositories\VaccineScheduleRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('repository.base', function ($app, array $parameters) {
            $modelClass = $parameters['model'] ?? null;

            abort_if(
                empty($modelClass) || ! is_subclass_of($modelClass, Model::class),
                500,
                'Parameter "model" must be a valid Eloquent Model class name.'
            );

            return new ModelRepository(new $modelClass());
        });

        // Repository Bindings for Module 1
        $this->app->bind(HealthCenterRepositoryInterface::class, HealthCenterRepository::class);
        $this->app->bind(VaccineRepositoryInterface::class, VaccineRepository::class);
        $this->app->bind(VaccineStockRepositoryInterface::class, VaccineStockRepository::class);
        $this->app->bind(VaccineScheduleRepositoryInterface::class, VaccineScheduleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
