<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\AccountCreating as AccountCreatingContract;

class AccountCreating extends AccountEvent implements AccountCreatingContract
{
}
