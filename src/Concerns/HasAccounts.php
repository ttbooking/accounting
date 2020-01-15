<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait HasAccounts.
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
