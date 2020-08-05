<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\TransactionCanceled as TransactionCanceledContract;

class TransactionCanceled extends TransactionEvent implements TransactionCanceledContract
{
}
