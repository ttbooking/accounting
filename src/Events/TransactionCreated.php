<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\TransactionCreated as TransactionCreatedContract;

class TransactionCreated extends TransactionEvent implements TransactionCreatedContract
{
}
