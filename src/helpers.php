<?php

use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\Transaction;
use Daniser\Accounting\Facades\Ledger;
use Daniser\Accounting\Models\Account;
use Money\Money;

if (! function_exists('transfer_money')) {
    /**
     * @param AccountContract|string $from
     * @param AccountContract|string $to
     * @param Money|string|int|float $amount
     * @param array|null $payload
     *
     * @return Transaction
     */
    function transfer_money($from, $to, $amount, array $payload = null): Transaction
    {
        if (! $from instanceof AccountContract) {
            $from = Account::resolve($from);
        }

        if (! $to instanceof AccountContract) {
            $to = Account::resolve($to);
        }

        if (! $amount instanceof Money) {
            $amount = Ledger::parseMoney($amount);
        }

        return $from->transferMoney($to, $amount, $payload);
    }
}
