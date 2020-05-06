<?php

namespace Daniser\Accounting\Events;

use Daniser\Accounting\Contracts\Account;

abstract class AccountEvent
{
    /** @var Account */
    public Account $account;

    /**
     * Create a new event instance.
     *
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }
}
