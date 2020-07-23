<?php

namespace TTBooking\Accounting\Support;

use DateTimeInterface;
use Illuminate\Support\Enumerable;
use Money\Money;
use TTBooking\Accounting\Concerns\HasBalance;
use TTBooking\Accounting\Contracts\Account;
use TTBooking\Accounting\Contracts\AccountPair as AccountPairContract;
use TTBooking\Accounting\Models\Transaction;

class AccountPair implements AccountPairContract
{
    use HasBalance;

    /** @var Account */
    protected Account $origin;

    /** @var Account  */
    protected Account $destination;

    /**
     * AccountPair constructor.
     *
     * @param Account $origin
     * @param Account $destination
     */
    public function __construct(Account $origin, Account $destination)
    {
        $this->origin = $origin;
        $this->destination = $destination;
    }

    public function inverse(): self
    {
        return new self($this->destination, $this->origin);
    }

    public function getOrigin(): Account
    {
        return $this->origin;
    }

    public function getDestination(): Account
    {
        return $this->destination;
    }

    public function getTransactions(bool $bidirectional = false): Enumerable
    {
        return Transaction::query()
            ->where('origin_id', $this->origin->getAccountKey())
            ->where('destination_id', $this->destination->getAccountKey())
            ->cursor();
    }

    public function getIncome(DateTimeInterface $byDate = null): Money
    {
        // TODO: Implement getIncome() method.
    }

    public function getExpense(DateTimeInterface $byDate = null): Money
    {
        // TODO: Implement getExpense() method.
    }
}
