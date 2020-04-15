<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Contracts\Transaction;
use Money\Currency;
use Money\Money;

trait ForwardsCallsToAccount
{
    public function getAccountKey()
    {
        return $this->getAccount()->getAccountKey();
    }

    public function getOwner(): AccountOwner
    {
        return $this;
    }

    public function getType(): string
    {
        return $this->getAccount()->getType();
    }

    public function getCurrency(): Currency
    {
        return $this->getAccount()->getCurrency();
    }

    public function getBalance(bool $fix = false): Money
    {
        return $this->getAccount()->getBalance($fix);
    }

    public function isBalanceValid(): bool
    {
        return $this->getAccount()->isBalanceValid();
    }

    public function transferMoney(self $recipient, Money $amount, array $payload = null): Transaction
    {
        return $this->getAccount()->transferMoney($recipient, $amount, $payload);
    }
}
