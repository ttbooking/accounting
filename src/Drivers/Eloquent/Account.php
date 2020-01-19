<?php

namespace Daniser\Accounting\Drivers\Eloquent;

use Daniser\Accounting\Contracts;
use Daniser\Accounting\Drivers\AccountAbstract;
use Daniser\Accounting\Models;
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
        return $this->model->owner;
    }

    public function getBalance(): Money
    {
        return $this->ledger->parseMoney($this->model->balance, $this->getCurrency());
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->model->currency);
    }

    public function getLimit(): Money
    {
        return $this->ledger->parseMoney($this->model->limit, $this->getCurrency());
    }

    public function setLimit(Money $limit): void
    {
        $this->model->limit = $this->ledger->formatMoney($this->ledger->convertMoney($limit, $this->getCurrency()));
    }

    public function transfer(Contracts\Account $recipient, Money /*|int*/ $amount, array $payload = null): Transaction
    {
        return $this->ledger->newTransaction($this, $recipient, $amount, $payload);
    }

    public function increment(Money $amount): void
    {
        $amount = $this->ledger->convertMoney($amount, $this->getCurrency());
        $this->ledger->getUseMoneyCalculator()
            ? $this->model->update(['balance' => $this->ledger->formatMoney($this->getBalance()->add($amount))])
            : $this->model->increment('balance', $this->ledger->formatMoney($amount));
    }

    public function decrement(Money $amount): void
    {
        $amount = $this->ledger->convertMoney($amount, $this->getCurrency());
        $this->ledger->getUseMoneyCalculator()
            ? $this->model->update(['balance' => $this->ledger->formatMoney($this->getBalance()->subtract($amount))])
            : $this->model->decrement('balance', $this->ledger->formatMoney($amount));
    }
}
