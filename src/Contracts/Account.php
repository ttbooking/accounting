<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use Money\Currency;
use Money\Money;

interface Account
{
    public function getAccountKey();

    public function getOwner(): AccountOwner;

    public function getAccountType(): string;

    public function getCurrency(): Currency;

    public function getBalance(bool $fix = false): Money;

    public function isBalanceValid(): bool;

    /**
     * Transfer money to another account.
     *
     * @param Account $destination
     * @param Money $amount
     * @param array|null $payload
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     *
     * @return Transaction
     */
    public function transferMoney(self $destination, Money $amount, array $payload = null): Transaction;
}
