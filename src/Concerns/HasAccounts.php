<?php

namespace Daniser\Accounting\Concerns;

use BadMethodCallException;
use Daniser\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;

/**
 * Trait HasAccounts.
 *
 * @mixin \Daniser\Accounting\Contracts\Account
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
     * @return Model|Account
     */
    public function getAccount(string $type = null, Currency $currency = null): Account
    {
        return $this->accounts()->firstOrCreate([
            'type' => $type ?? config('accounting.account.default_type'),
            'currency' => isset($currency) ? $currency->getCode() : config('accounting.account.default_currency'),
        ]);
    }

    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (BadMethodCallException $e) {
            return $this->forwardCallTo($this->getAccount(), $method, $parameters);
        }
    }
}
