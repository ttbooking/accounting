<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Money\Currency;

interface AccountOwner extends Account
{
    public function getIdentifier();

    public function getOwnerType(): string;

    public function getLender(): ?self;

    public function setLender(self $lender = null);

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
