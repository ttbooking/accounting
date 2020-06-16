<?php

namespace TTBooking\Accounting\Console;

use Illuminate\Console\Command;
use TTBooking\Accounting\Contracts\TransactionManager;

class TransactionCancelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:cancel
        {uuid : Transaction UUID or "all" to cancel all uncommitted transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel ongoing financial transaction(s)';

    /**
     * Execute the console command.
     *
     * @param TransactionManager $manager
     *
     * @return void
     */
    public function handle(TransactionManager $manager)
    {
        $uuid = $this->argument('uuid');

        $transactions = $uuid === 'all' ? $manager->uncommitted() : collect([$manager->get($uuid)]);

        if ($transactions->isEmpty()) {
            $this->info('Nothing to cancel.');
        }

        foreach ($transactions as $transaction) {
            $transaction->cancel();
            $this->info(sprintf('Transaction <comment>%s</comment> successfully canceled.', $transaction->getKey()));
        }
    }
}
