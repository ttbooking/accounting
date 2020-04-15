<?php

namespace Daniser\Accounting\Contracts;

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
     * @param Money $amount
     * @param array|null $payload
     *
     * @return Transaction
     */
    public function transferMoney(self $recipient, Money $amount, array $payload = null): Transaction;
}
