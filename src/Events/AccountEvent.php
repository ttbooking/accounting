<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Account;
use TTBooking\Accounting\Contracts\Events\AccountEvent as AccountEventContract;

abstract class AccountEvent implements AccountEventContract
{
    /** @var Account */
    protected Account $account;

    /**
     * Create a new event instance.
     *
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }
}
