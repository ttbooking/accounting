<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Money\Currency;
use Money\Money;

interface Account
{
    public function getAccountKey();

    public function getOwner(): AccountOwner;

    public function getType(): string;

    public function getCurrency(): Currency;

    public function getBalance(bool $fix = false): Money;

    public function isBalanceValid(): bool;

    /**
     * @param Account $recipient
     * @param Money|string $amount
     * @param array|null $payload
     *
     * @throws TransactionIdenticalEndpointsException
     *
     * @return Transaction
     */
    public function transferMoney(self $recipient, $amount, array $payload = null): Transaction;
}
