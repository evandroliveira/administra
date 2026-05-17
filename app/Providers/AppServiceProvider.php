<?php

namespace App\Providers;

use App\Support\DjangoCompatibleHasher;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Hashing\HashManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app['config']->set('hashing.driver', env('HASH_DRIVER', 'django_compatible'));

        $this->app->afterResolving('hash', function (HashManager $manager): void {
            $manager->extend('django_compatible', function ($app) {
                return new DjangoCompatibleHasher(
                    new BcryptHasher($app['config']->get('hashing.bcrypt', []))
                );
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $appUrl = rtrim((string) config('app.url'), '/');

        if (config('app.force_root_url') && $appUrl !== '') {
            URL::forceRootUrl($appUrl);
        }

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}
