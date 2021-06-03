<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Support;

use Money\Currencies;
use Money\Currency;
use Traversable;

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

    public function contains(Currency $currency): bool
    {
        return $this->currencies->contains($currency);
    }

    public function subunitFor(Currency $currency): int
    {
        return $this->currencies->subunitFor($currency) + $this->precision;
    }

    public function getIterator(): Traversable
    {
        return $this->currencies->getIterator();
    }
}
