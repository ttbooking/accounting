<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Facades\Ledger;
use Money\Currency;

trait AccountOwner
{
    public function getAccount($type = null, Currency $currency = null): Account
    {
        return Ledger::getAccount($this, $type, $currency);
    }
}
