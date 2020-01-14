<?php

namespace Daniser\Accounting\Contracts;

use Money\Currency;
use Daniser\Accounting\Exceptions\AccountNotFoundException;

interface AccountOwner extends Account
{
    public function getIdentifier();

    public function getOwnerType(): string;

    public function getLender(): ?AccountOwner;

    public function setLender(AccountOwner $lender = null);

    /**
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @throws AccountNotFoundException
     *
     * @return Account
     */
    public function getAccount($type = null, Currency $currency = null): Account;
}
