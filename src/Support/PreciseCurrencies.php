<?php

namespace Daniser\Accounting\Support;

use Money\Currencies;
use Money\Currency;

class PreciseCurrencies implements Currencies
{
    /** @var Currencies */
    protected Currencies $currencies;

    /** @var int */
    protected int $precision;

    /**
     * PreciseCurrencies constructor.
     *
     * @param Currencies $currencies
     * @param int $precision
     */
    public function __construct(Currencies $currencies, int $precision = 0)
    {
        $this->currencies = $currencies;
        $this->precision = $precision;
    }

    public function contains(Currency $currency)
    {
        return $this->currencies->contains($currency);
    }

    public function subunitFor(Currency $currency)
    {
        return $this->currencies->subunitFor($currency) + $this->precision;
    }

    public function getIterator()
    {
        return $this->currencies->getIterator();
    }
}
