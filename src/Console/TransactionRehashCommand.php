<?php

namespace TTBooking\Accounting\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use TTBooking\Accounting\Models\Transaction;

class TransactionRehashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:rehash
        {--from= : Transaction UUID or timestamp from which to rehash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rehash transaction ledger';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($from = $this->option('from')) {
            $from = Str::isUuid($from)
                ? Transaction::query()->findOrFail($from, 'finished_at')->getAttribute('finished_at')
                : Carbon::parse($from);
            $transactions = Transaction::committed()->where('finished_at', '>=', $from)->cursor();
        } else {
            $transactions = Transaction::committed()->cursor();
        }

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
