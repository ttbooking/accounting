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
        {uuid : Transaction UUID}
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
        $amount = $this->argument('amount');
        $commit = $this->option('commit');

        $amount = isset($amount) ? $ledger->parseMoney($amount) : null;

        $transaction = $manager->get($this->argument('uuid'));
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
