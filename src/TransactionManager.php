<?php

declare(strict_types=1);

namespace TTBooking\Accounting;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Money\Currency;
use Money\Money;
use TTBooking\Accounting\Contracts\Account;
use TTBooking\Accounting\Contracts\Ledger;
use TTBooking\Accounting\Contracts\Transaction as TransactionContract;
use TTBooking\Accounting\Exceptions\TransactionCreateAbortedException;
use TTBooking\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use TTBooking\Accounting\Exceptions\TransactionNegativeAmountException;
use TTBooking\Accounting\Exceptions\TransactionNotFoundException;
use TTBooking\Accounting\Exceptions\TransactionZeroTransferException;
use TTBooking\Accounting\Models\Transaction;
use TTBooking\EntityLocator\Contracts\EntityLocator;
use TTBooking\EntityLocator\Exceptions\EntityNotFoundException;

class TransactionManager implements Contracts\TransactionManager
{
    /** @var DatabaseManager */
    protected DatabaseManager $db;

    /** @var Ledger */
    protected Ledger $ledger;

    /** @var EntityLocator */
    protected EntityLocator $locator;

    /** @var Repository */
    protected Repository $config;

    /**
     * TransactionManager constructor.
     *
     * @param DatabaseManager $db
     * @param Ledger $ledger
     * @param EntityLocator $locator
     * @param Repository $config
     */
    public function __construct(DatabaseManager $db, Ledger $ledger, EntityLocator $locator, Repository $config)
    {
        $this->db = $db;
        $this->ledger = $ledger;
        $this->locator = $locator;
        $this->config = $config;
    }

    public function getTable(): string
    {
        return (new Transaction)->getTable();
    }

    /**
     * Get base currency configured for the ledger.
     *
     * @return Currency
     */
    public function baseCurrency(): Currency
    {
        return new Currency($this->config->get('accounting.base_transaction_currency'));
    }

    /**
     * Choose default currency for transaction.
     *
     * @param Account $origin
     * @param Account $destination
     *
     * @return Currency
     */
    public function currency(Account $origin, Account $destination): Currency
    {
        $base = $this->baseCurrency();
        $currency = $this->config->get('accounting.default_transaction_currency');

        switch (true) {
            case ! isset($$currency): return new Currency($currency);
            case $$currency instanceof Currency: return $$currency;
            case $$currency instanceof Account: return $$currency->getCurrency();
            default: throw new Exceptions\TransactionException('Default currency misconfiguration.');
        }
    }

    public function digest(TransactionContract $current, string $previousDigest = null): string
    {
        $algorithm = $this->config->get('accounting.blockchain.algorithm');
        $key = $this->config->get('accounting.blockchain.key');
        $baseCurrency = $this->config->get('accounting.base_transaction_currency');

        return hash_hmac($algorithm, $previousDigest.$baseCurrency.$current->toJson(), $key);
    }

    /**
     * Create new transaction.
     *
     * @param Account $origin
     * @param Account $destination
     * @param Money $amount
     * @param array|null $payload
     * @param TransactionContract|null $parent
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     * @throws TransactionNegativeAmountException
     * @throws TransactionCreateAbortedException
     *
     * @return Transaction|Model
     */
    public function create(
        Account $origin,
        Account $destination,
        Money $amount,
        array $payload = null,
        TransactionContract $parent = null
    ): Transaction {
        if ($origin->getAccountKey() === $destination->getAccountKey()) {
            throw new TransactionIdenticalEndpointsException('Transaction endpoints are identical.');
        }

        if ($amount->isZero() && ! $this->config->get('accounting.allow_zero_transfers')) {
            throw new TransactionZeroTransferException('Transaction of zero amount is forbidden.');
        }

        if ($amount->isNegative()) {
            if (! $this->config->get('accounting.handle_negative_amounts')) {
                throw new TransactionNegativeAmountException('Transaction of negative amount is forbidden.');
            }

            return $this->create($destination, $origin, $amount->absolute(), $payload, $parent);
        }

        $addendum = ! $this->config->get('accounting.origin_forward_conversion') ? []
            : ['origin_amount' => $this->ledger->serializeMoney(
                $this->ledger->convertMoney($amount, $origin->getCurrency())
            )];

        return tap(Transaction::query()->create([
            'parent_uuid' => isset($parent) ? $parent->getKey() : null,
            'origin_uuid' => $origin->getAccountKey(),
            'destination_uuid' => $destination->getAccountKey(),
            'currency' => $amount->getCurrency()->getCode(),
            'amount' => $this->ledger->serializeMoney($amount),
            'payload' => $payload,
        ] + $addendum), function (Transaction $transaction) use ($origin, $destination) {
            $origin instanceof Model && $transaction->origin()->associate($origin);
            $destination instanceof Model && $transaction->destination()->associate($destination);
            $this->config->get('accounting.auto_commit_transactions') && $transaction->commit();
        });
    }

    /**
     * Retrieve transaction by its Universally Unique Identifier (UUID).
     *
     * @param string $uuid
     *
     * @throws TransactionNotFoundException
     *
     * @return Transaction|Model
     */
    public function get(string $uuid): Transaction
    {
        try {
            return Transaction::query()->findOrFail($uuid);
        } catch (ModelNotFoundException $e) {
            throw new TransactionNotFoundException('Transaction not found.', $e->getCode(), $e);
        }
    }

    /**
     * Retrieve all transactions.
     *
     * @param bool $descending
     *
     * @return LazyCollection|Transaction[]
     */
    public function all(bool $descending = false): LazyCollection
    {
        return Transaction::query()->orderBy((new Transaction)->getKeyName(), $descending ? 'desc' : 'asc')->cursor();
    }

    /**
     * Retrieve all uncommitted transactions.
     *
     * @param bool $descending
     *
     * @return LazyCollection|Transaction[]
     */
    public function uncommitted(bool $descending = false): LazyCollection
    {
        return Transaction::uncommitted($descending ? 'desc' : 'asc')->cursor();
    }

    /**
     * Retrieve all committed transactions.
     *
     * @param bool $descending
     *
     * @return LazyCollection|Transaction[]
     */
    public function committed(bool $descending = false): LazyCollection
    {
        return Transaction::committed($descending ? 'desc' : 'asc')->cursor();
    }

    /**
     * Retrieve all canceled transactions.
     *
     * @param bool $descending
     *
     * @return LazyCollection|Transaction[]
     */
    public function canceled(bool $descending = false): LazyCollection
    {
        return Transaction::canceled($descending ? 'desc' : 'asc')->cursor();
    }

    /**
     * Retrieve all revertable transactions.
     *
     * @param bool $descending
     *
     * @return LazyCollection|Transaction[]
     */
    public function revertable(bool $descending = false): LazyCollection
    {
        return Transaction::revertable($descending ? 'desc' : 'asc')->cursor();
    }

    /**
     * Retrieve transaction by its address.
     *
     * @param mixed $address
     *
     * @throws TransactionNotFoundException
     *
     * @return Transaction|object
     */
    public function locate($address): Transaction
    {
        try {
            return $this->locator->locate(Transaction::class, $address);
        } catch (EntityNotFoundException $e) {
            throw new TransactionNotFoundException('Transaction not found.', $e->getCode(), $e);
        }
    }

    public function validate(): void
    {
        // TODO: Implement validate() method.
    }

    public function total(DateTimeInterface $byDate = null): Money
    {
        $query = Transaction::query()->where('status', Transaction::STATUS_COMMITTED);

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return $this->ledger->deserializeMoney($query->sum('base_amount'), $this->baseCurrency());
    }

    public function incomePerAccount(DateTimeInterface $byDate = null): Collection
    {
        return $this->incomeOrExpensePerAccount(true, $byDate);
    }

    public function expensePerAccount(DateTimeInterface $byDate = null): Collection
    {
        return $this->incomeOrExpensePerAccount(false, $byDate);
    }

    public function totalPerAccount(DateTimeInterface $byDate = null): Collection
    {
        $incomePerAccount = $this->incomePerAccount($byDate);
        $expensePerAccount = $this->expensePerAccount($byDate);

        return $incomePerAccount->merge($expensePerAccount)->map(
            function (Money $money, string $key) use ($incomePerAccount, $expensePerAccount) {
                $zero = new Money(0, $money->getCurrency());
                return ($incomePerAccount[$key] ?? $zero)->subtract($expensePerAccount[$key] ?? $zero);
            }
        );
    }

    /**
     * Money amounts debited or credited per account.
     *
     * @param bool $income
     * @param DateTimeInterface|null $byDate
     *
     * @return Collection|Money[]
     */
    protected function incomeOrExpensePerAccount(bool $income, DateTimeInterface $byDate = null): Collection
    {
        $accounts = (new Models\Account)->getTable();
        $transactions = $this->getTable();

        $key = $income ? 'destination_uuid' : 'origin_uuid';
        $amount = $income ? 'destination_amount' : 'origin_amount';

        $query = $this->db->query()
            ->selectRaw("$key as uuid, $accounts.currency, sum($amount) as sum")
            ->from($transactions)
            ->join($accounts, "$accounts.uuid", "$transactions.$key")
            ->where('status', Transaction::STATUS_COMMITTED)
            ->groupBy('uuid');

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return $query->get()->mapWithKeys(function ($row) {
            settype($row, 'object');
            return [$row->uuid => $this->ledger->deserializeMoney($row->sum, new Currency($row->currency))];
        });
    }
}
