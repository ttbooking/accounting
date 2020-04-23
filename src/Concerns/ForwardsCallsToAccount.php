<?php

namespace Daniser\Accounting\Concerns;

use BadMethodCallException;
use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Contracts\Transaction;
use Money\Currency;
use Money\Money;

/**
 * Trait ForwardsCallsToAccount.
 *
 * @mixin \Daniser\Accounting\Contracts\Account
 */
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

    public function transferMoney(Account $recipient, $amount, array $payload = null): Transaction
    {
        return $this->getAccount()->transferMoney($recipient, $amount, $payload);
    }

    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (BadMethodCallException $e) {
            return $this->forwardCallTo($this->getAccount(), $method, $parameters);
        }
    }
}
