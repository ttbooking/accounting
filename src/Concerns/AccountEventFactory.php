<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Concerns;

use TTBooking\Accounting\Contracts\Events\AccountCreated as AccountCreatedContract;
use TTBooking\Accounting\Contracts\Events\AccountCreating as AccountCreatingContract;
use TTBooking\Accounting\Events;

trait AccountEventFactory
{
    /**
     * @return AccountCreatingContract
     */
    protected function newAccountCreatingEvent(): AccountCreatingContract
    {
        return new Events\AccountCreating($this);
    }

    /**
     * @return AccountCreatedContract
     */
    protected function newAccountCreatedEvent(): AccountCreatedContract
    {
        return new Events\AccountCreated($this);
    }
}
