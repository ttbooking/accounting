<?php

namespace TTBooking\Accounting\Contracts;

use DateTimeInterface;
use Money\Money;

interface Reportable
{
    public function getIncome(DateTimeInterface $byDate = null): Money;

    public function getExpense(DateTimeInterface $byDate = null): Money;

    public function getBalance(DateTimeInterface $byDate = null): Money;
}
