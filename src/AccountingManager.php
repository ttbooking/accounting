<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Drivers\Eloquent\Ledger;
use Illuminate\Support\Manager;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Parser\DecimalMoneyParser;

/**
 * @mixin \Daniser\Accounting\Contracts\Ledger
 */
class AccountingManager extends Manager
{
    protected function createEloquentDriver()
    {
        $currencies = new ISOCurrencies;

        return $this->container->make(Ledger::class, [
            'config' => $this->container['config']['accounting'],
            'parser' => new DecimalMoneyParser($currencies),
            'formatter' => new DecimalMoneyFormatter($currencies),
        ]);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('accounting.driver', 'eloquent');
    }
}
