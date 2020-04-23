<?php

namespace Daniser\Accounting\Contracts;

use Money\Currency;
use Money\Money;
use Money\MoneyParser;
use Money\Exception\ParserException;

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
