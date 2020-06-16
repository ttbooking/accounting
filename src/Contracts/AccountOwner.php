<?php

namespace TTBooking\Accounting\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Money\Currency;
use TTBooking\Accounting\Exceptions\AccountNotFoundException;
use TTBooking\Accounting\Models\Account as AccountModel;

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
