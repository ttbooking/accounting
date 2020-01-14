<?php

namespace Daniser\Accounting\Events;

use Daniser\Accounting\Contracts\Transaction;

abstract class TransactionEvent
{
    /** @var Transaction $transaction */
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
