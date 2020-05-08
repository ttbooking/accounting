<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Support\ExtendedDecimalMoneyFormatter;
use Daniser\Accounting\Support\ExtendedDecimalMoneyParser;
use Daniser\Accounting\Support\PreciseCurrencies;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Money\Converter;
use Money\Currencies;
use Money\Exchange;
use Money\MoneyFormatter;
use Money\MoneyParser;

class MoneyServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public array $singletons = [
        Currencies::class => Currencies\ISOCurrencies::class,
        MoneyFormatter::class => ExtendedDecimalMoneyFormatter::class,
        MoneyParser::class => ExtendedDecimalMoneyParser::class,
        Exchange::class => Exchange\SwapExchange::class,
        Converter::class => Converter::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->extend(Currencies::class, function (Currencies $currencies) {
            return new PreciseCurrencies($currencies, 2);
        });

        $this->app->extend(Exchange::class, function (Exchange $exchange) {
            return new Exchange\IndirectExchange(
                new Exchange\ReversedCurrenciesExchange($exchange),
                $this->app->make(Currencies::class)
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_keys($this->singletons);
    }
}
