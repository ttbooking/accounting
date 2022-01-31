<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Console;

use Illuminate\Contracts\Events\Dispatcher;
use TTBooking\Accounting\Contracts\TransactionManager;

class TransactionCommitCommand extends AccountingCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:commit
        {uuid : Transaction UUID or "all" to commit all uncommitted transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commit ongoing financial transaction(s)';

    /**
     * Execute the console command.
     *
     * @param  TransactionManager  $manager
     * @param  Dispatcher|null  $dispatcher
     * @return void
     */
    public function handle(TransactionManager $manager, Dispatcher $dispatcher = null)
    {
        $dispatcher && $this->registerEventAnnouncers($dispatcher);

        $uuid = $this->argument('uuid');

        $transactions = $uuid === 'all' ? $manager->uncommitted() : collect([$manager->get($uuid)]);

        if ($transactions->isEmpty()) {
            $this->info('Nothing to commit.');
        }

        foreach ($transactions as $transaction) {
            $transaction->commit();
        }
    }
}
