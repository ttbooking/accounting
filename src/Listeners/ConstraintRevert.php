<?php

namespace TTBooking\Accounting\Listeners;

use TTBooking\Accounting\Events\TransactionCommitting;

class ConstraintRevert
{
    /**
     * Handle the event.
     *
     * @param TransactionCommitting $event
     *
     * @return bool|null
     */
    public function handle(TransactionCommitting $event)
    {
        $parent = $event->transaction->getParent();

        if (! is_null($parent)) {
            $fullyReverted = $parent->isReverted();
            $exceedingRemainder = $event->transaction->getAmount()->greaterThan($parent->getRemainingAmount());

            return $fullyReverted || $exceedingRemainder ? false : null;
        }
    }
}
