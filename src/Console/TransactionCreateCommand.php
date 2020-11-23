<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Console;

use Illuminate\Contracts\Events\Dispatcher;
use TTBooking\Accounting\Contracts\AccountManager;
use TTBooking\Accounting\Contracts\Ledger;
use TTBooking\Accounting\Contracts\TransactionManager;

class TransactionCreateCommand extends AccountingCommand
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
        $dispatcher && $this->registerEventAnnouncers($dispatcher);

        $transaction
            ->enableAutoCommit($this->option('commit'))
            ->create(
                $origin = $account->locate($from = $this->argument('from')),
                $destination = $account->locate([$to = $this->argument('to'), $from]),
                $ledger->parseMoney($this->argument('amount'), $transaction->currency($origin, $destination))
            );
    }
}
