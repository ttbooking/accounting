<?php

namespace Daniser\Accounting\Events;

use Daniser\Accounting\Contracts\Transaction;
use Throwable;

class TransactionFailed extends TransactionEvent
{
    /** @var Throwable */
    public Throwable $exception;

    /**
     * Create a new event instance.
     *
     * @param Transaction $transaction
     * @param Throwable $exception
     */
    public function __construct(Transaction $transaction, Throwable $exception)
    {
        parent::__construct($transaction);
        $this->exception = $exception;
    }
}
