<?php

namespace Daniser\Accounting;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class AccountingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/accounting.php' => config_path('accounting.php')
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations')
            ], 'migrations');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accounting.php', 'accounting');

        $this->app->singleton(Contracts\Ledger::class, Ledger::class);

        $this->app->alias(Contracts\Ledger::class, 'ledger');

        $this->app->when(Ledger::class)->needs('$config')->give(fn($app) => $app['config']['accounting']);
    }

    public function provides()
    {
        return [Contracts\Ledger::class, 'ledger'];
    }
}
