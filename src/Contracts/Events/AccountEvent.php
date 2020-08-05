<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts\Events;

use TTBooking\Accounting\Contracts\Account;

interface AccountEvent
{
    /**
     * @return Account
     */
    public function getAccount(): Account;
}
