<?php

use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\Transaction;
use Daniser\Accounting\Models\Account;
use Money\Money;

if (! function_exists('transfer_money')) {
    /**
     * @param AccountContract|string $from
     * @param AccountContract|string $to
     * @param Money|string $amount
     * @param array|null $payload
     *
     * @return Transaction
     */
    function transfer_money($from, $to, $amount, array $payload = null): Transaction
    {
        if (! $to instanceof AccountContract) {
            if (! $from instanceof AccountContract) {
                $to = [$to, $from];
            }
            $to = Account::resolve($to);
        }

        if (! $from instanceof AccountContract) {
            $from = Account::resolve($from);
        }

        return $from->transferMoney($to, $amount, $payload);
    }
}
