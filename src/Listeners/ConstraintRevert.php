<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Listeners;

use TTBooking\Accounting\Events\TransactionCommitting;

class ConstraintRevert
{
    /**
     * Handle the event.
     *
     * @param  TransactionCommitting  $event
     * @return bool|null
     */
    public function handle(TransactionCommitting $event)
    {
        $parent = $event->getTransaction()->getParent();

        if (! is_null($parent)) {
            $fullyReverted = $parent->isReverted();
            $exceedingRemainder = $event->getTransaction()->getAmount()->greaterThan($parent->getRemainingAmount());

            return $fullyReverted || $exceedingRemainder ? false : null;
        }
    }
}
