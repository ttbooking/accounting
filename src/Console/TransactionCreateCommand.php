<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use TTBooking\Accounting\Contracts\AccountManager;
use TTBooking\Accounting\Contracts\Ledger;
use TTBooking\Accounting\Contracts\Transaction;
use TTBooking\Accounting\Contracts\TransactionManager;
use TTBooking\Accounting\Events\AccountCreated;

class TransactionCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:create
        {from : Origin account address}
        {to : Destination account address}
        {amount : Money amount (with optional currency)}
        {--c|commit : Commit transaction afterwards}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new financial transaction';

    /**
     * Execute the console command.
     *
     * @param TransactionManager $transaction
     * @param AccountManager $account
     * @param Ledger $ledger
     * @param Dispatcher|null $dispatcher
     *
     * @return void
     */
    public function handle(TransactionManager $transaction, AccountManager $account, Ledger $ledger, Dispatcher $dispatcher = null)
    {
        $dispatcher && $dispatcher->listen(AccountCreated::class, function (AccountCreated $event) {
            $this->info(sprintf('Account <comment>%s</comment> successfully created.', $event->getAccount()->getAccountKey()));
        });

        $transfer = $transaction->create(
            $origin = $account->locate($from = $this->argument('from')),
            $destination = $account->locate([$to = $this->argument('to'), $from]),
            $amount = $ledger->parseMoney($this->argument('amount'), $transaction->currency($origin, $destination))
        );

        $lines = [
            'Transaction <comment>%s</comment> worth <comment>%s</comment> successfully created.',
            'Transaction <comment>%s</comment> %s.',
        ];

        $this->info(sprintf($lines[0], $transfer->getKey(), $ledger->formatMoney($amount)));

        if ($this->option('commit') && $transfer->getStatus() === Transaction::STATUS_STARTED) {
            $transfer->commit();
        }

        if ($transfer->getStatus() !== Transaction::STATUS_STARTED) {
            $this->info(sprintf($lines[1],
                $transfer->getKey(),
                $transfer->getStatus() === Transaction::STATUS_COMMITTED
                    ? 'successfully committed' : 'canceled',
            ));
        }
    }
}
