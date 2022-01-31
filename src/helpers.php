<?php

declare(strict_types=1);

use Money\Money;
use TTBooking\Accounting\Contracts\Account as AccountContract;
use TTBooking\Accounting\Contracts\Transaction as TransactionContract;
use TTBooking\Accounting\Facades\Account;
use TTBooking\Accounting\Facades\Ledger;
use TTBooking\Accounting\Facades\Transaction;

if (! function_exists('transfer_money')) {
    /**
     * @param  AccountContract|string  $from
     * @param  AccountContract|string  $to
     * @param  Money|string  $amount
     * @param  array|null  $payload
     * @return TransactionContract
     */
    function transfer_money($from, $to, $amount, array $payload = null): TransactionContract
    {
        if (is_string($from) && is_string($to)) {
            $to = [$to, $from];
        }

        return Transaction::create(
            $from = Account::locate($from),
            $to = Account::locate($to),
            Ledger::parseMoney($amount, Transaction::currency($from, $to)),
            $payload
        );
    }
}
