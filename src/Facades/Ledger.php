<?php

namespace TTBooking\Accounting\Facades;

use Illuminate\Support\Facades\Facade;
use Money\Currency;
use Money\Money;

/**
 * @method static mixed transaction(\Closure $callback, int $attempts = null)
 * @method static mixed fireEvent(string|object $event, mixed $payload = [], bool $halt = true)
 * @method static string serializeMoney(Money $money)
 * @method static Money deserializeMoney(string $money, Currency $fallbackCurrency = null)
 * @method static string formatMoney(Money $money)
 * @method static Money parseMoney(string $money, Currency $fallbackCurrency = null)
 * @method static Money convertMoney(Money $money, Currency $counterCurrency, int $roundingMode = null)
 *
 * @see \TTBooking\Accounting\Ledger::class
 */
class Ledger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TTBooking\Accounting\Contracts\Ledger::class;
    }
}
