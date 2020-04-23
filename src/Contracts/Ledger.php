<?php

namespace Daniser\Accounting\Contracts;

use Money\Currency;
use Money\Money;

interface Ledger
{
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
