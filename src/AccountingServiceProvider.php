<?php

namespace Daniser\Accounting;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Parser\DecimalMoneyParser;

class AccountingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public array $singletons = [
        Contracts\AccountManager::class => AccountManager::class,
        Contracts\TransactionManager::class => TransactionManager::class,
    ];

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
                Console\TransactionCreateCommand::class,
                Console\TransactionCommitCommand::class,
                Console\TransactionCancelCommand::class,
                Console\TransactionRevertCommand::class,
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
                $this->app['config']['accounting.default_owner_type'],
                $this->app['config']['accounting.default_account_type'],
                new Currency($this->app['config']['accounting.default_account_currency']),
                $this->app['config']['entity-resolver.composite_delimiter']
            );
        });

        $this->app->singleton(Contracts\Ledger::class, function () {
            return $this->app->make(Ledger::class, [
                'serializer' => $this->app->make(DecimalMoneyFormatter::class),
                'deserializer' => $this->app->make(DecimalMoneyParser::class),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_keys($this->singletons), [Support\AccountResolver::class, Contracts\Ledger::class]);
    }
}
