<?php

namespace TTBooking\Accounting\Support;

use Money\Currencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Parser\DecimalMoneyParser;
use TTBooking\Accounting\Contracts\SafeMoneyParser;

class ExtendedDecimalMoneyParser implements SafeMoneyParser
{
    const PREFIXED_CURRENCY_PATTERN = '/^(?P<currency>[A-Za-z]{3})? ?(?P<decimal>-?(?:0|[1-9]\d*)?\.?\d+?)$/';

    const SUFFIXED_CURRENCY_PATTERN = '/^(?P<decimal>-?(?:0|[1-9]\d*)?\.?\d+?) ?(?P<currency>[A-Za-z]{3})?$/';

    /** @var DecimalMoneyParser */
    protected DecimalMoneyParser $parser;

    /**
     * @param Currencies $currencies
     */
    public function __construct(Currencies $currencies)
    {
        $this->parser = new DecimalMoneyParser($currencies);
    }

    public function parse($money, $fallbackCurrency = null)
    {
        if (! is_string($money)) {
            throw new ParserException('Formatted raw money should be string, e.g. USD1.00');
        }

        $money = trim($money);

        if (! preg_match(self::PREFIXED_CURRENCY_PATTERN, $money, $matches) &&
            ! preg_match(self::SUFFIXED_CURRENCY_PATTERN, $money, $matches)) {
            throw new ParserException(sprintf('Cannot parse "%s" to Money.', $money));
        }

        $currency = $matches['currency'] ? new Currency(strtoupper($matches['currency'])) : $fallbackCurrency;

        return $this->parser->parse($matches['decimal'], $currency);
    }
}
