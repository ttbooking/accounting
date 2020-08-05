<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\AccountCreated as AccountCreatedContract;

class AccountCreated extends AccountEvent implements AccountCreatedContract
{
}
