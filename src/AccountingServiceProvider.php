<?php

namespace Daniser\Accounting;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Parser\DecimalMoneyParser;

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
                __DIR__.'/../config/accounting.php' => $this->app->configPath('accounting.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'migrations');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->commands([
                Console\LedgerTransferCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accounting.php', 'accounting');

        $this->app->singleton(Contracts\Ledger::class, function () {
            $currencies = new ISOCurrencies;

            return $this->app->make(Ledger::class, [
                'config' => $this->app['config']['accounting'],
                'serializer' => new DecimalMoneyFormatter($currencies),
                'deserializer' => new DecimalMoneyParser($currencies),
            ]);
        });

        $this->app->alias(Contracts\Ledger::class, 'ledger');
    }

    public function provides()
    {
        return [Contracts\Ledger::class, 'ledger'];
    }
}
