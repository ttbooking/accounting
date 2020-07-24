<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts;

use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\MoneyParser;

interface SafeMoneyParser extends MoneyParser
{
    /**
     * @param string $money
     * @param Currency|string|null $fallbackCurrency
     *
     * @throws ParserException
     *
     * @return Money
     */
    public function parse($money, $fallbackCurrency = null);
}
