<?php

namespace Daniser\Accounting\Console;

use Daniser\Accounting\Contracts\TransactionManager;
use Illuminate\Console\Command;

class TransactionCancelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:cancel {uuid : Transaction UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel ongoing financial transaction';

    /**
     * Execute the console command.
     *
     * @param TransactionManager $manager
     *
     * @return void
     */
    public function handle(TransactionManager $manager)
    {
        $manager->get($uuid = $this->argument('uuid'))->cancel();

        $this->info("Transaction <comment>{$uuid}</comment> successfully canceled.");
    }
}
