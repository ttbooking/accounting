<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Listeners;

use TTBooking\Accounting\Contracts\Ledger;
use TTBooking\Accounting\Events\TransactionCommitting;

class CheckBalance
{
    /** @var Ledger */
    protected Ledger $ledger;

    /**
     * Create the event listener.
     *
     * @param Ledger $ledger
     */
    public function __construct(Ledger $ledger)
    {
        $this->ledger = $ledger;
    }

    /**
     * Handle the event.
     *
     * @param TransactionCommitting $event
     *
     * @return bool
     */
    public function handle(TransactionCommitting $event)
    {
        $balance = $event->getTransaction()->getOrigin()->getBalance();
        $amount = $this->ledger->convertMoney($event->getTransaction()->getAmount(), $balance->getCurrency());

        if ($insufficientFunds = $balance->lessThan($amount)) {
            report(new \RuntimeException('Transaction canceled: insufficient funds.'));
        }

        return $insufficientFunds ? false : null;
    }
}
