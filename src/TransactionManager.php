<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\Ledger;
use Daniser\Accounting\Contracts\Transaction as TransactionContract;
use Daniser\Accounting\Exceptions\TransactionCreateAbortedException;
use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionNegativeAmountException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use Daniser\Accounting\Models\Transaction;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityNotFoundException;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Money\Currency;
use Money\Money;

class TransactionManager implements Contracts\TransactionManager
{
    /** @var Ledger */
    protected Ledger $ledger;

    /** @var EntityResolver */
    protected EntityResolver $resolver;

    /** @var array */
    protected array $config;

    /**
     * TransactionManager constructor.
     *
     * @param Ledger $ledger
     * @param EntityResolver $resolver
     * @param array $config
     */
    public function __construct(Ledger $ledger, EntityResolver $resolver, array $config = [])
    {
        $this->ledger = $ledger;
        $this->resolver = $resolver;
        $this->config = $config;
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
        $currency = $this->config['default_currency'];

        return isset($$currency) && $$currency instanceof Account
            ? $$currency->getCurrency()
            : new Currency($currency);
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

        if ($amount->isZero() && ! $this->config['allow_zero_transfers']) {
            throw new TransactionZeroTransferException('Transaction of zero amount is forbidden.');
        }

        if ($amount->isNegative()) {
            if (! $this->config['handle_negative_amounts']) {
                throw new TransactionNegativeAmountException('Transaction of negative amount is forbidden.');
            }

            return $this->create($destination, $origin, $amount->absolute(), $payload, $parent);
        }

        return tap(Transaction::query()->create([
            'parent_uuid' => isset($parent) ? $parent->getKey() : null,
            'origin_uuid' => $origin->getAccountKey(),
            'destination_uuid' => $destination->getAccountKey(),
            'currency' => $amount->getCurrency()->getCode(),
            'amount' => $this->ledger->serializeMoney($amount),
            'payload' => $payload,
        ]), function (Transaction $transaction) {
            if (! $transaction->exists) {
                throw new TransactionCreateAbortedException('Transaction creation aborted.');
            }

            $this->config['auto_commit'] && $transaction->commit();
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
            return $this->resolver->resolve(Transaction::class, $address);
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
        $query = Transaction::query();

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return $this->ledger->deserializeMoney($query->sum('amount'));
    }

    public function incomePerAccount(DateTimeInterface $byDate = null): Collection
    {
        return $this->incomeOrExpensePerAccount(true, $byDate)->map(fn ($sum) => $this->ledger->deserializeMoney($sum));
    }

    public function expensePerAccount(DateTimeInterface $byDate = null): Collection
    {
        return $this->incomeOrExpensePerAccount(false, $byDate)->map(fn ($sum) => $this->ledger->deserializeMoney($sum));
    }

    public function totalPerAccount(DateTimeInterface $byDate = null): Collection
    {
        $incomePerAccount = $this->incomeOrExpensePerAccount(true, $byDate);
        $expensePerAccount = $this->incomeOrExpensePerAccount(false, $byDate);

        $keys = $incomePerAccount->merge($expensePerAccount)->keys()->mapWithKeys(fn ($key) => [$key => '0']);

        $incomePerAccount = $incomePerAccount->union($keys);
        $expensePerAccount = $expensePerAccount->union($keys);

        return $incomePerAccount->mergeRecursive($expensePerAccount)->mapSpread(function ($income, $expense) {
            return $this->ledger->deserializeMoney($income)->subtract($this->ledger->deserializeMoney($expense));
        });
    }

    /**
     * Money amounts debited or credited per account.
     *
     * @param bool $income
     * @param DateTimeInterface|null $byDate
     *
     * @return Collection|string[]
     */
    protected function incomeOrExpensePerAccount(bool $income, DateTimeInterface $byDate = null): Collection
    {
        $key = $income ? 'destination_uuid' : 'origin_uuid';

        $query = Transaction::query()
            ->selectRaw('sum(amount) as sum, '.$key)
            ->where('status', Transaction::STATUS_COMMITTED)
            ->groupBy($key);

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return $query->pluck('sum', $key);
    }
}
