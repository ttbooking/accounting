<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\TransactionCreateAbortedException;
use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionNegativeAmountException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Money\Currency;
use Money\Money;

interface TransactionManager
{
    /**
     * Choose default currency for transaction.
     *
     * @param Account $origin
     * @param Account $destination
     *
     * @return Currency
     */
    public function currency(Account $origin, Account $destination): Currency;

    /**
     * Create new transaction.
     *
     * @param Account $origin
     * @param Account $destination
     * @param Money $amount
     * @param array|null $payload
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     * @throws TransactionNegativeAmountException
     * @throws TransactionCreateAbortedException
     *
     * @return Transaction
     */
    public function create(Account $origin, Account $destination, Money $amount, array $payload = null): Transaction;

    /**
     * Retrieve transaction by its Universally Unique Identifier (UUID).
     *
     * @param string $uuid
     *
     * @throws TransactionNotFoundException
     *
     * @return Transaction
     */
    public function get(string $uuid): Transaction;

    /**
     * Retrieve transaction by its address.
     *
     * @param mixed $address
     *
     * @throws TransactionNotFoundException
     *
     * @return Transaction
     */
    public function locate($address): Transaction;

    public function validate(): void;

    /**
     * Total amount of money transfers.
     *
     * @param DateTimeInterface|null $byDate
     *
     * @return Money
     */
    public function total(DateTimeInterface $byDate = null): Money;

    /**
     * Money amounts debited per account.
     *
     * @param DateTimeInterface|null $byDate
     *
     * @return Collection|Money[]
     */
    public function incomePerAccount(DateTimeInterface $byDate = null): Collection;

    /**
     * Money amounts credited per account.
     *
     * @param DateTimeInterface|null $byDate
     *
     * @return Collection|Money[]
     */
    public function expensePerAccount(DateTimeInterface $byDate = null): Collection;

    /**
     * Money balance per account.
     *
     * @param DateTimeInterface|null $byDate
     *
     * @return Collection|Money[]
     */
    public function totalPerAccount(DateTimeInterface $byDate = null): Collection;
}
