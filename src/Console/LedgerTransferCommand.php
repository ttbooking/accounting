<?php

namespace Daniser\Accounting\Console;

use Illuminate\Console\Command;

class LedgerTransferCommand extends Command
{
    protected $signature = 'ledger:transfer
        {from : Source account identifier}
        {to : Destination account identifier}
        {amount : Money amount (with optional currency)}';

    protected $description = 'Transfer money from one account to another.';

    public function handle()
    {
        $from = $this->argument('from');
        $to = $this->argument('to');
        $amount = $this->argument('amount');

        // TODO: option to force commit (--commit)
        // TODO: option to disable events (--no-events)

        transfer_money($from, $to, $amount)->commit();
    }
}
