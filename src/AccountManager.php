<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Contracts\Ledger;
use Daniser\Accounting\Contracts\TransactionManager;
use Daniser\Accounting\Exceptions\AccountCreateAbortedException;
use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Models\Account;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Money\Currency;
use Money\Money;

class AccountManager implements Contracts\AccountManager
{
    /** @var EntityResolver */
    protected EntityResolver $resolver;

    /** @var TransactionManager */
    protected TransactionManager $transaction;

    /** @var Ledger */
    protected Ledger $ledger;

    /** @var array */
    protected array $config;

    /**
     * AccountManager constructor.
     *
     * @param EntityResolver $resolver
     * @param TransactionManager $transaction
     * @param Ledger $ledger
     * @param array $config
     */
    public function __construct(EntityResolver $resolver, TransactionManager $transaction, Ledger $ledger, array $config = [])
    {
        $this->resolver = $resolver;
        $this->transaction = $transaction;
        $this->ledger = $ledger;
        $this->config = $config;
    }

    public function getTable(): string
    {
        return (new Account)->getTable();
    }

    /**
     * Create new account or retrieve one if it exists.
     *
     * @param AccountOwner $owner
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @throws AccountCreateAbortedException
     *
     * @return Account|Model
     */
    public function create(AccountOwner $owner, string $type = null, Currency $currency = null): Account
    {
        return tap(
            $owner->accounts()->firstOrCreate($this->prepareAttributes($type, $currency)),
            function (Account $account) {
                if (! $account->exists) {
                    throw new AccountCreateAbortedException('Account creation aborted.');
                }
            }
        );
    }

    /**
     * Find account by its owner, type and currency.
     *
     * @param AccountOwner $owner
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @throws AccountCreateAbortedException
     * @throws AccountNotFoundException
     *
     * @return Account|Model
     */
    public function find(AccountOwner $owner, string $type = null, Currency $currency = null): Account
    {
        if ($this->config['auto_create']) {
            return $this->create($owner, $type, $currency);
        }

        try {
            return $owner->accounts()->where($this->prepareAttributes($type, $currency))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new AccountNotFoundException('Account not found.', $e->getCode(), $e);
        }
    }

    /**
     * Retrieve account by its Universally Unique Identifier (UUID).
     *
     * @param string $uuid
     *
     * @throws AccountNotFoundException
     *
     * @return Account|Model
     */
    public function get(string $uuid): Account
    {
        try {
            return Account::query()->findOrFail($uuid);
        } catch (ModelNotFoundException $e) {
            throw new AccountNotFoundException('Account not found.', $e->getCode(), $e);
        }
    }

    /**
     * Retrieve all existing accounts, or all accounts belonging to a single owner.
     *
     * @param AccountOwner|null $owner
     *
     * @return LazyCollection|Account[]
     */
    public function all(AccountOwner $owner = null): LazyCollection
    {
        return is_null($owner) ? Account::query()->cursor() : $owner->accounts()->cursor();
    }

    /**
     * Retrieve account by its address.
     *
     * @param mixed $address
     *
     * @throws AccountNotFoundException
     *
     * @return Account|object
     */
    public function locate($address): Account
    {
        try {
            return $this->resolver->resolve(Account::class, $address);
        } catch (EntityNotFoundException $e) {
            throw new AccountNotFoundException('Account not found.', $e->getCode(), $e);
        }
    }

    public function delete(AccountContract $account): void
    {
        // TODO: Implement delete() method.
    }

    public function purge($onlyBlank = true): void
    {
        // TODO: Implement purge() method.
    }

    public function totalPerAccount(): Collection
    {
        return Account::all('uuid', 'currency', 'balance')
            ->mapWithKeys(fn (Account $account) => [$account->getKey() => $account->getBalance()]);
    }

    public function invalidTotalPerAccount(): Collection
    {
        $totalsFromAccounts = $this->totalPerAccount();
        $totalsFromTransactions = $this->transaction->totalPerAccount();

        return $totalsFromAccounts->reject(fn (Money $total, string $uuid) =>
            isset($totalsFromTransactions[$uuid]) ? $total->equals($totalsFromTransactions[$uuid]) : $total->isZero()
        );
    }

    public function isValid(bool $aggressive = false): bool
    {
        return $aggressive ? $this->invalidTotalPerAccount()->isEmpty() : Account::query()->sum('balance') == 0;
    }

    public function fix(): void
    {
    }

    /**
     * Prepare model attributes for creation and retrieval methods.
     *
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @return array
     */
    protected function prepareAttributes(string $type = null, Currency $currency = null)
    {
        return [
            'type' => $type ?? $this->config['default_type'],
            'currency' => isset($currency) ? $currency->getCode() : $this->config['default_currency'],
        ];
    }
}
