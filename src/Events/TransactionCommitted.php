<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\TransactionCommitted as TransactionCommittedContract;

class TransactionCommitted extends TransactionEvent implements TransactionCommittedContract
{
}
