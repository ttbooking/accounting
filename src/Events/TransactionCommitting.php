<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\TransactionCommitting as TransactionCommittingContract;

class TransactionCommitting extends TransactionEvent implements TransactionCommittingContract
{
}
