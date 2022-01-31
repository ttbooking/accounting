<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Listeners;

use TTBooking\Accounting\Events\TransactionEvent;

class TestListener
{
    /**
     * Handle the event.
     *
     * @param  TransactionEvent  $event
     * @return bool
     */
    public function handle(TransactionEvent $event)
    {
        $transaction = $event->getTransaction();
    }
}
