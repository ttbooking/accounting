<?php

namespace Daniser\Accounting\Console;

use Illuminate\Console\Command;

class LedgerTransferCommand extends Command
{
    protected $signature = 'ledger:transfer
        {from : Origin account address}
        {to : Destination account address}
        {amount : Money amount (with optional currency)}';

    protected $description = 'Transfer money from one account to another.';

    public function handle()
    {
        // TODO: option to force commit (--commit)
        // TODO: option to disable events (--no-events)

        transfer_money(
            $this->argument('from'),
            $this->argument('to'),
            $this->argument('amount'),
        )->commit();
    }
}
