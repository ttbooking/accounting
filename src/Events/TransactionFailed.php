<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Events;

use Throwable;
use TTBooking\Accounting\Contracts\Events\TransactionFailed as TransactionFailedContract;
use TTBooking\Accounting\Contracts\Transaction;

class TransactionFailed extends TransactionEvent implements TransactionFailedContract
{
    /** @var Throwable|null */
    protected ?Throwable $exception;

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

    public function getException(): ?Throwable
    {
        return $this->exception;
    }
}
