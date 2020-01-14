<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\Ledger;
use Money\Currency;

trait AccountOwner
{
    public function getAccount($type = null, Currency $currency = null): Account
    {
        return app(Ledger::class)->getAccount($this, $type, $currency);
    }
}
