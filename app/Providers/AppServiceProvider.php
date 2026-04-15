<?php

namespace App\Providers;

use App\Repositories\Base\ModelRepository;
use App\Repositories\BookingRepositoryInterface;
use App\Repositories\EloquentBookingRepository;
use App\Repositories\ScheduleRepositoryInterface;
use App\Repositories\EloquentScheduleRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Base repository binding (existing)
        $this->app->bind('repository.base', function ($app, array $parameters) {
            $modelClass = $parameters['model'] ?? null;

            abort_if(
                empty($modelClass) || ! is_subclass_of($modelClass, Model::class),
                500,
                'Parameter "model" must be a valid Eloquent Model class name.'
            );

            return new ModelRepository(new $modelClass());
        });

        // Module 3: Queue & Appointment repository bindings
        $this->app->bind(BookingRepositoryInterface::class, EloquentBookingRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, EloquentScheduleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
