<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\TransactionReverting as TransactionRevertingContract;

class TransactionReverting extends TransactionEvent implements TransactionRevertingContract
{
}
