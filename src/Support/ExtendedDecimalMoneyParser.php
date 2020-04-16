<?php

namespace Daniser\Accounting\Support;

use Money\Currencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\MoneyParser;
use Money\Parser\DecimalMoneyParser;

class ExtendedDecimalMoneyParser implements MoneyParser
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

    public function parse($money, $forceCurrency = null)
    {
        if (! is_string($money)) {
            throw new ParserException('Formatted raw money should be string, e.g. USD1.00');
        }

        $money = trim($money);

        if (! preg_match(self::PREFIXED_CURRENCY_PATTERN, $money, $matches) &&
            ! preg_match(self::SUFFIXED_CURRENCY_PATTERN, $money, $matches)) {
            throw new ParserException(sprintf('Cannot parse "%s" to Money.', $money));
        }

        $currency = $matches['currency'] ? new Currency(strtoupper($matches['currency'])) : $forceCurrency;

        return $this->parser->parse($matches['decimal'], $currency);
    }
}
