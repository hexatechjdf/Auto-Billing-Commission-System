<?php
namespace App\Providers;

// use App\Repositories\OAuthRepository;
// use App\Services\DocuSignService;
// use App\Services\DropboxService;
// use App\Services\OAuthService;
// use Illuminate\Support\Facades\URL;
use App\Repositories\LocationSettingRepository;
use App\Repositories\ServiceTitanCredentialRepository;
use App\Services\LocationSettingService;
use App\Services\TitanCredentialService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ServiceTitanCredentialRepository::class, fn() => new ServiceTitanCredentialRepository());
        $this->app->singleton(LocationSettingRepository::class, fn() => new LocationSettingRepository());
        $this->app->singleton(TitanCredentialService::class, fn($app) => new TitanCredentialService($app->make(ServiceTitanCredentialRepository::class)));
        $this->app->singleton(LocationSettingService::class, fn($app) => new LocationSettingService($app->make(LocationSettingRepository::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // User::observe(UserObserver::class);

        // if (app()->environment('local')) {
        //     URL::forceScheme('https');
        // }

        // DB::listen(function ($query) {
        //     Log::info('Query Executed: ' . $query->sql);
        //     Log::info('Bindings: ', $query->bindings);
        //     Log::info('Time: ' . $query->time . ' ms'); // Log the query execution time
        // });
        //
    }
}
