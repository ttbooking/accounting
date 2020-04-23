<?php

namespace Daniser\Accounting\Support;

use Daniser\Accounting\Contracts\SafeMoneyParser;
use Money\Exception\ParserException;
use Money\MoneyParser;

class FallbackMoneyParser implements SafeMoneyParser
{
    /** @var MoneyParser */
    protected MoneyParser $parser;

    /**
     * @param MoneyParser $parser
     */
    public function __construct(MoneyParser $parser)
    {
        $this->parser = $parser;
    }

    public function parse($money, $fallbackCurrency = null)
    {
        if ($this->parser instanceof SafeMoneyParser) {
            return $this->parser->parse($money, $fallbackCurrency);
        }

        try {
            return $this->parser->parse($money);
        } catch (ParserException $e) {
            if ($fallbackCurrency && preg_match('/forceCurrency/', $e->getMessage())) {
                return $this->parser->parse($money, $fallbackCurrency);
            }

            throw $e;
        }
    }
}
