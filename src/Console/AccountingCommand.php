<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use TTBooking\Accounting\Contracts\Events;
use TTBooking\Accounting\Facades\Ledger;

abstract class AccountingCommand extends Command
{
    /**
     * @param Dispatcher $dispatcher
     *
     * @return void
     */
    protected function registerEventAnnouncers(Dispatcher $dispatcher)
    {
        $dispatcher->listen(Events\AccountCreated::class, function (Events\AccountCreated $event) {
            $this->info(sprintf(
                'Account <comment>%s</comment> successfully created.',
                $event->getAccount()->getAccountKey()
            ));
        });

        $dispatcher->listen(Events\TransactionCreated::class, function (Events\TransactionCreated $event) {
            $this->info(sprintf(
                'Transaction <comment>%s</comment> worth <comment>%s</comment> successfully created.',
                $event->getTransaction()->getKey(),
                Ledger::formatMoney($event->getTransaction()->getAmount())
            ));
        });

        $dispatcher->listen(Events\TransactionCommitted::class, function (Events\TransactionCommitted $event) {
            $this->info(sprintf(
                'Transaction <comment>%s</comment> successfully committed.',
                $event->getTransaction()->getKey()
            ));
        });

        $dispatcher->listen(Events\TransactionCanceled::class, function (Events\TransactionCanceled $event) {
            $this->info(sprintf(
                'Transaction <comment>%s</comment> canceled.',
                $event->getTransaction()->getKey()
            ));
        });
    }
}
