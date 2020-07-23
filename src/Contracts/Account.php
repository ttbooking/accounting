<?php

namespace TTBooking\Accounting\Contracts;

use Money\Currency;
use Money\Money;
use TTBooking\Accounting\Exceptions\TransactionCreateAbortedException;
use TTBooking\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use TTBooking\Accounting\Exceptions\TransactionNegativeAmountException;
use TTBooking\Accounting\Exceptions\TransactionZeroTransferException;

interface Account extends Reportable
{
    public function getAccountKey();

    public function getOwner(): AccountOwner;

    public function getAccountType(): string;

    public function getCurrency(): Currency;

    public function getContext(string $key = null, $default = null);

    public function isBalanceValid(): bool;

    public function fixBalance(): void;

    /**
     * Transfer money to another account.
     *
     * @param Account $destination
     * @param Money $amount
     * @param array|null $payload
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     * @throws TransactionNegativeAmountException
     * @throws TransactionCreateAbortedException
     *
     * @return Transaction
     */
    public function transferMoney(self $destination, Money $amount, array $payload = null): Transaction;
}
