<?php

namespace Daniser\Accounting\Support;

use Money\Currencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;

class ExtendedDecimalMoneyFormatter implements MoneyFormatter
{
    /** @var DecimalMoneyFormatter */
    protected DecimalMoneyFormatter $formatter;

    /**
     * @param Currencies $currencies
     */
    public function __construct(Currencies $currencies)
    {
        $this->formatter = new DecimalMoneyFormatter($currencies);
    }

    public function format(Money $money)
    {
        return $money->getCurrency()->getCode().$this->formatter->format($money);
    }
}
