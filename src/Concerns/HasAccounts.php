<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Facades\Account;
use Daniser\Accounting\Models\Account as AccountModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Money\Currency;

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
