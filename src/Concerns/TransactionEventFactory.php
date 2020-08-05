<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Concerns;

use Throwable;
use TTBooking\Accounting\Contracts\Events\TransactionCanceled as TransactionCanceledContract;
use TTBooking\Accounting\Contracts\Events\TransactionCommitted as TransactionCommittedContract;
use TTBooking\Accounting\Contracts\Events\TransactionCommitting as TransactionCommittingContract;
use TTBooking\Accounting\Contracts\Events\TransactionCreated as TransactionCreatedContract;
use TTBooking\Accounting\Contracts\Events\TransactionCreating as TransactionCreatingContract;
use TTBooking\Accounting\Contracts\Events\TransactionFailed as TransactionFailedContract;
use TTBooking\Accounting\Contracts\Events\TransactionReverting as TransactionRevertingContract;
use TTBooking\Accounting\Events;

trait TransactionEventFactory
{
    /**
     * @return TransactionCreatingContract
     */
    protected function newTransactionCreatingEvent(): TransactionCreatingContract
    {
        return new Events\TransactionCreating($this);
    }

    /**
     * @return TransactionCreatedContract
     */
    protected function newTransactionCreatedEvent(): TransactionCreatedContract
    {
        return new Events\TransactionCreated($this);
    }

    /**
     * @return TransactionCommittingContract
     */
    protected function newTransactionCommittingEvent(): TransactionCommittingContract
    {
        return new Events\TransactionCommitting($this);
    }

    /**
     * @return TransactionCommittedContract
     */
    protected function newTransactionCommittedEvent(): TransactionCommittedContract
    {
        return new Events\TransactionCommitted($this);
    }

    /**
     * @return TransactionCanceledContract
     */
    protected function newTransactionCanceledEvent(): TransactionCanceledContract
    {
        return new Events\TransactionCanceled($this);
    }

    /**
     * @return TransactionRevertingContract
     */
    protected function newTransactionRevertingEvent(): TransactionRevertingContract
    {
        return new Events\TransactionReverting($this);
    }

    /**
     * @param Throwable|null $exception
     *
     * @return TransactionFailedContract
     */
    protected function newTransactionFailedEvent(Throwable $exception = null): TransactionFailedContract
    {
        return new Events\TransactionFailed($this, $exception);
    }
}
