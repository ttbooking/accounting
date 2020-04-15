<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Money\Currency;

interface AccountOwner //extends Account
{
    /**
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @throws AccountNotFoundException
     *
     * @return Account
     */
    public function getAccount(string $type = null, Currency $currency = null): Account;
}
