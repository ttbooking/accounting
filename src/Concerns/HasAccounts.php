<?php

namespace TTBooking\Accounting\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Money\Currency;
use TTBooking\Accounting\Exceptions\AccountNotFoundException;
use TTBooking\Accounting\Facades\Account;
use TTBooking\Accounting\Models\Account as AccountModel;

/**
 * Trait HasAccounts.
 *
 * @mixin Model
 * @property Collection|AccountModel[] $accounts
 */
trait HasAccounts
{
    /**
     * @return MorphMany
     */
    public function accounts(): MorphMany
    {
        return $this->morphMany(AccountModel::class, 'owner');
    }

    /**
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @throws AccountNotFoundException
     *
     * @return AccountModel|Model
     */
    public function getAccount(string $type = null, Currency $currency = null): AccountModel
    {
        return Account::find($this, $type, $currency);
    }
}
