<?php

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\Transaction;
use Daniser\Accounting\Facades\Ledger;
use Money\Money;

if (! function_exists('transfer_money')) {
    /**
     * @param Account|string $from
     * @param Account|string $to
     * @param Money|string|int|float $amount
     * @param array|null $payload
     *
     * @return Transaction
     */
    function transfer_money($from, $to, $amount, array $payload = null): Transaction
    {
        $moneyParser = Ledger::getMoneyParser();

        if (! $from instanceof Account) {
            /** @var Account $from */
            $from = Ledger::locateAccount($from);
        }

        if (! $to instanceof Account) {
            /** @var Account $to */
            $to = Ledger::locateAccount($to);
        }

        if (! $amount instanceof Money) {
            /** @var Money $amount */
            $amount = $moneyParser->parse($amount);
        }

        return $from->transfer($to, $amount, $payload);
    }
}
