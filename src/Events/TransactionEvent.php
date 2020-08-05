<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use TTBooking\Accounting\Contracts\Events\TransactionEvent as TransactionEventContract;
use TTBooking\Accounting\Contracts\Transaction;

abstract class TransactionEvent implements TransactionEventContract
{
    /** @var Transaction */
    protected Transaction $transaction;

    /**
     * Create a new event instance.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
