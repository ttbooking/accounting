<?php

namespace Daniser\Accounting\Console;

use Daniser\Accounting\Contracts\Transaction;
use Daniser\Accounting\Contracts\TransactionManager;
use Illuminate\Console\Command;

class TransactionCommitCommand extends Command
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
     * @param TransactionManager $manager
     *
     * @return void
     */
    public function handle(TransactionManager $manager)
    {
        $uuid = $this->argument('uuid');

        $transactions = $uuid === 'all' ? $manager->uncommitted() : collect([$manager->get($uuid)]);

        if ($transactions->isEmpty()) {
            $this->info('Nothing to commit.');
        }

        foreach ($transactions as $transaction) {
            $transaction->commit();
            $this->info(sprintf('Transaction <comment>%s</comment> %s.',
                $transaction->getKey(),
                $transaction->getStatus() === Transaction::STATUS_COMMITTED
                    ? 'successfully committed' : 'canceled',
            ));
        }
    }
}
