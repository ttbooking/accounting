<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts;

use DateTimeInterface;
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

    public function getIncome(DateTimeInterface $byDate = null, self $origin = null): Money;

    public function getExpense(DateTimeInterface $byDate = null, self $destination = null): Money;

    public function getBalance(DateTimeInterface $byDate = null, self $other = null): Money;

    public function isBalanceValid(): bool;

    public function fixBalance(): void;

    /**
     * Transfer money to another account.
     *
     * @param  Account  $destination
     * @param  Money  $amount
     * @param  array|null  $payload
     * @return Transaction
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     * @throws TransactionNegativeAmountException
     * @throws TransactionCreateAbortedException
     */
    public function transferMoney(self $destination, Money $amount, array $payload = null): Transaction;
}
