<?php

namespace Daniser\Accounting\Events;

use Daniser\Accounting\Contracts\Transaction;
use Throwable;

class TransactionFailed extends TransactionEvent
{
    /** @var Throwable|null */
    public ?Throwable $exception;

    /**
     * Create a new event instance.
     *
     * @param Transaction $transaction
     * @param Throwable|null $exception
     */
    public function __construct(Transaction $transaction, Throwable $exception = null)
    {
        parent::__construct($transaction);
        $this->exception = $exception;
    }
}
