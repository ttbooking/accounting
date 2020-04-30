<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\TransactionCreateAbortedException;
use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionNegativeAmountException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use DateTimeInterface;
use Money\Currency;
use Money\Money;

interface Account
{
    public function getAccountKey();

    public function getOwner(): AccountOwner;

    public function getAccountType(): string;

    public function getCurrency(): Currency;

    public function getIncome(DateTimeInterface $byDate = null): Money;

    public function getExpense(DateTimeInterface $byDate = null): Money;

    public function getBalance(DateTimeInterface $byDate = null): Money;

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
