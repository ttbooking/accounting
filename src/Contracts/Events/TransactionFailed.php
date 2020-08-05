<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts\Events;

use Throwable;

interface TransactionFailed extends TransactionEvent
{
    /**
     * @return Throwable|null
     */
    public function getException(): ?Throwable;
}
