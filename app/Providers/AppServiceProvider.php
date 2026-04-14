<?php

namespace App\Providers;

use App\Repositories\Base\ModelRepository;
use App\Repositories\Patient\Contracts\HealthHistoryRepositoryInterface;
use App\Repositories\Patient\Contracts\PatientRepositoryInterface;
use App\Repositories\Patient\Contracts\VaccinationHistoryRepositoryInterface;
use App\Repositories\Patient\Eloquent\HealthHistoryRepository;
use App\Repositories\Patient\Eloquent\PatientRepository;
use App\Repositories\Patient\Eloquent\VaccinationHistoryRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PatientRepositoryInterface::class, PatientRepository::class);
        $this->app->bind(HealthHistoryRepositoryInterface::class, HealthHistoryRepository::class);
        $this->app->bind(VaccinationHistoryRepositoryInterface::class, VaccinationHistoryRepository::class);

        $this->app->bind('repository.base', function ($app, array $parameters) {
            $modelClass = $parameters['model'] ?? null;

            abort_if(
                empty($modelClass) || ! is_subclass_of($modelClass, Model::class),
                500,
                'Parameter "model" must be a valid Eloquent Model class name.'
            );

            return new ModelRepository(new $modelClass());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limitPerMinute = (int) env('API_RATE_LIMIT_PER_MINUTE', 120);

            return Limit::perMinute(max($limitPerMinute, 1))->by($request->ip());
        });
    }
}
