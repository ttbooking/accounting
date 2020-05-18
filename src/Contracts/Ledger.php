<?php

namespace Daniser\Accounting\Contracts;

use Closure;
use Money\Currency;
use Money\Money;
use Throwable;

interface Ledger
{
    /**
     * @param string|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function config(string $key = null, $default = null);

    /**
     * Execute a Closure within a transaction.
     *
     * @param Closure $callback
     * @param int|null $attempts
     *
     * @throws Throwable
     *
     * @return mixed
     */
    public function transaction(Closure $callback, $attempts = null);

    /**
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     *
     * @return mixed
     */
    public function fireEvent($event, $payload = [], $halt = true);

    /**
     * @param Money $money
     *
     * @return string
     */
    public function serializeMoney(Money $money): string;

    /**
     * @param string $money
     * @param Currency|null $fallbackCurrency
     *
     * @return Money
     */
    public function deserializeMoney(string $money, Currency $fallbackCurrency = null): Money;

    /**
     * @param Money $money
     *
     * @return string
     */
    public function formatMoney(Money $money): string;

    /**
     * @param string $money
     * @param Currency|null $fallbackCurrency
     *
     * @return Money
     */
    public function parseMoney(string $money, Currency $fallbackCurrency = null): Money;

    /**
     * @param Money $money
     * @param Currency $counterCurrency
     * @param int|null $roundingMode
     *
     * @return Money
     */
    public function convertMoney(Money $money, Currency $counterCurrency, $roundingMode = null): Money;
}
