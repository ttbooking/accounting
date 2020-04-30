<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Contracts\Ledger;
use Daniser\Accounting\Contracts\TransactionManager;
use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Models\Account;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as BaseCollection;
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

    /**
     * Create new account or retrieve one if it exists.
     *
     * @param AccountOwner $owner
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @return Account|Model
     */
    public function create(AccountOwner $owner, string $type = null, Currency $currency = null): Account
    {
        return $owner->accounts()->firstOrCreate($this->prepareAttributes($type, $currency));
    }

    /**
     * Find account by its owner, type and currency.
     *
     * @param AccountOwner $owner
     * @param string|null $type
     * @param Currency|null $currency
     *
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
     * @return Collection|Account[]
     */
    public function all(AccountOwner $owner = null): Collection
    {
        return is_null($owner) ? Account::all() : $owner->accounts()->get();
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

    public function isValid(bool $aggressive = false, BaseCollection $invalid = null): bool
    {
        if (! $aggressive) {
            return Account::query()->sum('balance') === 0;
        }

        $totalsFromAccounts = $this->totalPerAccount();
        $totalsFromTransactions = $this->transaction->totalPerAccount();

        $invalid = $totalsFromAccounts->reject(function (Money $total, string $uuid) use ($totalsFromTransactions) {
            return $total->equals($totalsFromTransactions[$uuid] ?? $this->ledger->deserializeMoney('0'));
        });

        return $invalid->isNotEmpty();
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

    /**
     * Money balance per account.
     *
     * @return BaseCollection|Money[]
     */
    protected function totalPerAccount(): BaseCollection
    {
        return Account::query()
            //->where('balance', '<>', 0)
            ->pluck('balance', 'uuid')
            ->map(fn ($sum) => $this->ledger->deserializeMoney($sum));
    }
}
