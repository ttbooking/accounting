<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Money\Currency;

/**
 * Trait HasAccounts.
 *
 * @property Collection|Account[] $accounts
 */
trait HasAccounts
{
    public function accounts()
    {
        return $this->morphMany(Account::class, 'owner');
    }

    public function getAccount(string $type = null, Currency $currency = null): Account
    {
        return $this->accounts()->where([
            'type' => $type ?? config('accounting.account.default_type'),
            'currency' => isset($currency) ? $currency->getCode() : config('accounting.account.default_currency'),
        ])->get();
    }
}
