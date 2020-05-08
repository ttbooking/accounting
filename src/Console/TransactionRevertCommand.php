<?php

namespace Daniser\Accounting\Console;

use Daniser\Accounting\Contracts\Ledger;
use Daniser\Accounting\Contracts\Transaction;
use Daniser\Accounting\Contracts\TransactionManager;
use Illuminate\Console\Command;

class TransactionRevertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:revert
        {uuid : Transaction UUID or "all" to revert all committed transactions}
        {amount? : Money amount to revert (reverts all remains if omitted)}
        {--c|commit : Commit transaction afterwards}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revert committed financial transaction';

    /**
     * Execute the console command.
     *
     * @param TransactionManager $manager
     * @param Ledger $ledger
     *
     * @return void
     */
    public function handle(TransactionManager $manager, Ledger $ledger)
    {
        $uuid = $this->argument('uuid');
        $amount = $this->argument('amount');
        $commit = $this->option('commit');

        $amount = $uuid !== 'all' && isset($amount) ? $ledger->parseMoney($amount) : null;

        $transactions = $uuid === 'all' ? $manager->committed() : collect([$manager->get($uuid)]);

        if ($transactions->isEmpty()) {
            $this->info('Nothing to revert.');
        }

        foreach ($transactions as $transaction) {
            $revertTransaction = $transaction->revert($amount);

            $lines = [
                'Revert transaction <comment>%s</comment> for transaction <comment>%s</comment> successfully created.',
                'Going to revert <comment>%s</comment> of remaining <comment>%s</comment> (<comment>%s</comment> total).',
                'Transaction <comment>%s</comment> %s.',
            ];

            $this->info(sprintf($lines[0], $revertTransaction->getKey(), $transaction->getKey()));

            $this->info(sprintf($lines[1],
                $ledger->formatMoney($revertTransaction->getAmount()),
                $ledger->formatMoney($transaction->getRemainingAmount()),
                $ledger->formatMoney($transaction->getAmount()),
            ));

            if ($commit && $revertTransaction->getStatus() === Transaction::STATUS_STARTED) {
                $revertTransaction->commit();
            }

            if ($revertTransaction->getStatus() !== Transaction::STATUS_STARTED) {
                $this->info(sprintf($lines[2],
                    $revertTransaction->getKey(),
                    $revertTransaction->getStatus() === Transaction::STATUS_COMMITTED
                        ? 'successfully committed' : 'canceled',
                ));
            }
        }
    }
}
