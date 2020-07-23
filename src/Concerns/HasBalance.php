<?php

namespace TTBooking\Accounting\Concerns;

use DateTimeInterface;
use Money\Money;

trait HasBalance
{
    abstract public function getIncome(DateTimeInterface $byDate = null): Money;

    abstract public function getExpense(DateTimeInterface $byDate = null): Money;

    public function getBalance(DateTimeInterface $byDate = null): Money
    {
        return $this->getIncome($byDate)->subtract($this->getExpense($byDate));
    }
}
