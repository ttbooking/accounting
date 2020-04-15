<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;

/**
 * Trait HasAccounts.
 *
 * @property Collection|Account[] $accounts
 */
trait HasAccounts
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function accounts()
    {
        return $this->morphMany(Account::class, 'owner');
    }

    /**
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @return AccountContract|Model
     */
    public function getAccount(string $type = null, Currency $currency = null): AccountContract
    {
        return $this->accounts()->firstOrCreate([
            'type' => $type ?? config('accounting.account.default_type'),
            'currency' => isset($currency) ? $currency->getCode() : config('accounting.account.default_currency'),
        ]);
    }
}
