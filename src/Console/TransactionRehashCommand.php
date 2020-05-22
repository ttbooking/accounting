<?php

namespace Daniser\Accounting\Console;

use Daniser\Accounting\Contracts\TransactionManager;
use Illuminate\Console\Command;

class TransactionRehashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:rehash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rehash transaction ledger';

    /**
     * Execute the console command.
     *
     * @param TransactionManager $manager
     *
     * @return void
     */
    public function handle(TransactionManager $manager)
    {
        $transactions = $manager->committed();
        $bar = $this->output->createProgressBar(count($transactions));

        $this->info('Rehashing transaction ledger...');
        $bar->start();
        foreach ($transactions as $transaction) {
            $transaction->updateDigest();
            $bar->advance();
        }
        $bar->finish();
        $this->info(PHP_EOL.'Done.');
    }
}
