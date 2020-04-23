<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Models\Account as AccountModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Money\Currency;

/**
 * @property Collection|AccountModel[] $accounts
 */
interface AccountOwner
{
    /**
     * @return MorphMany
     */
    public function accounts(): MorphMany;

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
