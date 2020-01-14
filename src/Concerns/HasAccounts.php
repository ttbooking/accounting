<?php

namespace Daniser\Accounting\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Daniser\Accounting\Models\Account;

/**
 * Trait HasAccounts
 * @package Daniser\Accounting\Concerns
 *
 * @property Collection|Account[] $accounts
 */
trait HasAccounts
{
    public function accounts()
    {
        $this->morphMany(Account::class, 'owner');
    }
}
