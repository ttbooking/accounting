<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts\Events;

use TTBooking\Accounting\Contracts\Transaction;

interface TransactionEvent
{
    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction;
}
