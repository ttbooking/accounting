<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts;

use Closure;
use Money\Currency;
use Money\Money;
use Throwable;

interface Ledger
{
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
    public function transaction(Closure $callback, int $attempts = null);

    /**
     * @param string|object $event
     * @param array $payload
     * @param bool $halt
     *
     * @return mixed
     */
    public function fireEvent($event, array $payload = [], bool $halt = true);

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
    public function convertMoney(Money $money, Currency $counterCurrency, int $roundingMode = null): Money;
}
