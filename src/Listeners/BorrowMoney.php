<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Listeners;

use Illuminate\Support\Str;
use TTBooking\Accounting\Contracts\AccountManager;
use TTBooking\Accounting\Contracts\Ledger;
use TTBooking\Accounting\Events\TransactionCommitting;

class BorrowMoney
{
    /** @var AccountManager */
    protected AccountManager $account;

    /** @var Ledger */
    protected Ledger $ledger;

    /**
     * Create the event listener.
     *
     * @param AccountManager $account
     * @param Ledger $ledger
     */
    public function __construct(AccountManager $account, Ledger $ledger)
    {
        $this->account = $account;
        $this->ledger = $ledger;
    }

    /**
     * Handle the event.
     *
     * @param TransactionCommitting $event
     *
     * @return void
     */
    public function handle(TransactionCommitting $event)
    {
        $origin = $event->getTransaction()->getOrigin();
        if (Str::startsWith($origin->getAccountType(), 'credit')) {
            $amountNeeded = $event->getTransaction()->getDestinationAmount();
            $needBorrow = $amountNeeded->subtract($origin->getBalance());
            if ($needBorrow->isPositive()) {
                $creditSource = $this->account->locate($origin->getContext('credit.source'));
                $creditLimit = $this->ledger->deserializeMoney(
                    $origin->getContext('credit.limit', 0),
                    $origin->getCurrency()
                );
                $creditBalance = $this->account->pair($origin, $creditSource)->getBalance();
                $canBorrow = $creditLimit->subtract($creditBalance);
                if ($needBorrow->lessThanOrEqual($canBorrow)) {
                    $creditSource->transferMoney($origin, $needBorrow)->commit();
                }
            }
        }
    }
}
