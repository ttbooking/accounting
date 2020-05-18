<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\TransactionCreateAbortedException;
use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionNegativeAmountException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Money\Currency;
use Money\Money;

interface TransactionManager
{
    /**
     * Get table name for transaction storage.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Get base currency configured for the ledger.
     *
     * @return Currency
     */
    public function baseCurrency(): Currency;

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
     * Calculate digest for the current transaction.
     *
     * @param Transaction $current
     * @param Transaction|null $previous
     *
     * @return string
     */
    public function digest(Transaction $current, Transaction $previous = null): string;

    /**
     * Create new transaction.
     *
     * @param Account $origin
     * @param Account $destination
     * @param Money $amount
     * @param array|null $payload
     * @param Transaction|null $parent
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     * @throws TransactionNegativeAmountException
     * @throws TransactionCreateAbortedException
     *
     * @return Transaction
     */
    public function create(
        Account $origin,
        Account $destination,
        Money $amount,
        array $payload = null,
        Transaction $parent = null
    ): Transaction;

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
     * Retrieve all transactions.
     *
     * @param bool $descending
     *
     * @return Enumerable|Transaction[]
     */
    public function all(bool $descending = false): Enumerable;

    /**
     * Retrieve all uncommitted transactions.
     *
     * @param bool $descending
     *
     * @return Enumerable|Transaction[]
     */
    public function uncommitted(bool $descending = false): Enumerable;

    /**
     * Retrieve all committed transactions.
     *
     * @param bool $descending
     *
     * @return Enumerable|Transaction[]
     */
    public function committed(bool $descending = false): Enumerable;

    /**
     * Retrieve all canceled transactions.
     *
     * @param bool $descending
     *
     * @return Enumerable|Transaction[]
     */
    public function canceled(bool $descending = false): Enumerable;

    /**
     * Retrieve all revertable transactions.
     *
     * @param bool $descending
     *
     * @return Enumerable|Transaction[]
     */
    public function revertable(bool $descending = false): Enumerable;

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
