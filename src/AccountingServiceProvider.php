<?php

namespace Daniser\Accounting;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
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

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accounting.php', 'accounting');

        $this->app->bind(Support\AccountResolver::class, function () {
            return new Support\AccountResolver(
                $this->app->make(Support\AccountOwnerResolver::class),
                $this->app['config']['accounting.owner.default_type'],
                $this->app['config']['accounting.account.default_type'],
                new Currency($this->app['config']['accounting.account.default_currency']),
                $this->app['config']['entity-resolver.composite_delimiter']
            );
        });

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

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Support\AccountResolver::class, Contracts\Ledger::class, 'ledger'];
    }
}
