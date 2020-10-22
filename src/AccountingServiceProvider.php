<?php

declare(strict_types=1);

namespace TTBooking\Accounting;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Money\Currency;
use TTBooking\CastableMoney\Deviators\Money as MoneyDeviator;
use TTBooking\MoneySerializer\Contracts\SerializesMoney;
use TTBooking\MoneySerializer\Serializers\DecimalMoneySerializer;

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
        Contracts\Ledger::class => Ledger::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
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
            Console\TransactionRehashCommand::class,
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/accounting.php', 'accounting');

        $this->app->bind(Support\AccountLocator::class, function () {
            return new Support\AccountLocator(
                $this->app->make(Support\AccountOwnerLocator::class),
                $this->app['config']['accounting.default_owner_type'],
                $this->app['config']['accounting.default_account_type'],
                new Currency($this->app['config']['accounting.default_account_currency']),
                $this->app['config']['entity-locator.composite_delimiter']
            );
        });

        $this->app
            ->when([Ledger::class, MoneyDeviator::class])
            ->needs(SerializesMoney::class)
            ->give(DecimalMoneySerializer::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_keys($this->singletons), [Support\AccountLocator::class]);
    }
}
