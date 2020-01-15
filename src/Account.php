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
        return $this->model->balance;
    }

    public function getCurrency(): Currency
    {
        return $this->model->currency;
    }

    public function getLimit(): Money
    {
        return $this->model->limit;
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
        $this->model->increment('balance', $this->ledger->convertMoney($amount, $this->getCurrency())->getAmount());
    }

    public function decrement(Money $amount): void
    {
        $this->model->decrement('balance', $this->ledger->convertMoney($amount, $this->getCurrency())->getAmount());
    }
}
