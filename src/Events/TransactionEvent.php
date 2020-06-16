<?php

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Transaction;

abstract class TransactionEvent
{
    /** @var Transaction */
    public Transaction $transaction;

    /**
     * Create a new event instance.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
