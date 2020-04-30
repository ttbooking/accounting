<?php

namespace Daniser\Accounting\Concerns;

use BadMethodCallException;
use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Contracts\Transaction;
use DateTimeInterface;
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

    public function getAccountType(): string
    {
        return $this->getAccount()->getType();
    }

    public function getCurrency(): Currency
    {
        return $this->getAccount()->getCurrency();
    }

    public function getIncome(DateTimeInterface $byDate = null): Money
    {
        return $this->getAccount()->getIncome($byDate);
    }

    public function getExpense(DateTimeInterface $byDate = null): Money
    {
        return $this->getAccount()->getExpense($byDate);
    }

    public function getBalance(DateTimeInterface $byDate = null): Money
    {
        return $this->getAccount()->getBalance($byDate);
    }

    public function isBalanceValid(): bool
    {
        return $this->getAccount()->isBalanceValid();
    }

    public function fixBalance(): void
    {
        $this->getAccount()->fixBalance();
    }

    public function transferMoney(Account $destination, Money $amount, array $payload = null): Transaction
    {
        return $this->getAccount()->transferMoney($destination, $amount, $payload);
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
