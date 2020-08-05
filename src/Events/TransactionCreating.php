<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\TransactionCreating as TransactionCreatingContract;

class TransactionCreating extends TransactionEvent implements TransactionCreatingContract
{
}
