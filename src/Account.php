<?php

namespace Daniser\Accounting;

use Money\Currency;
use Money\Money;

class Account extends AccountAbstract
{
    /** @var Ledger $ledger */
    protected Ledger $ledger;

    /** @var Models\Account $model */
    protected Models\Account $model;

    /**
     * Account constructor.
     *
     * @param Ledger $ledger
     * @param Models\Account $model
     */
    public function __construct(Ledger $ledger, Models\Account $model)
    {
        $this->ledger = $ledger;
        $this->model = $model;
    }

    public function getUniqueIdentifier()
    {
        return $this->model->getKey();
    }

    public function getType(): string
    {
        return $this->model->type;
    }

    public function getOwner(): Contracts\AccountOwner
    {
    }

    public function getBalance(): Money
    {
        return new Money($this->model->balance, $this->getCurrency());
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->model->currency);
    }

    public function getLimit(): Money
    {
        return new Money($this->model->limit, $this->getCurrency());
    }

    public function setLimit(Money $limit): void
    {
        $this->model->limit = $this->ledger->convertMoney($limit, $this->getCurrency())->getAmount();
    }

    public function transfer(Contracts\Account $recipient, Money /*|int*/ $amount, array $payload = null): Transaction
    {
        return $this->ledger->newTransaction($this, $recipient, $amount, $payload);
    }

    public function increment(Money $amount): void
    {
        $amount = $this->ledger->convertMoney($amount, $this->getCurrency());
        $this->ledger->getUseMoneyCalculator()
            ? $this->model->update(['balance' => $this->getBalance()->add($amount)->getAmount()])
            : $this->model->increment('balance', $amount->getAmount());
    }

    public function decrement(Money $amount): void
    {
        $amount = $this->ledger->convertMoney($amount, $this->getCurrency());
        $this->ledger->getUseMoneyCalculator()
            ? $this->model->update(['balance' => $this->getBalance()->subtract($amount)->getAmount()])
            : $this->model->decrement('balance', $amount->getAmount());
    }
}
