<?php

declare(strict_types=1);

namespace TTBooking\Accounting;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Money\Currency;
use Money\Money;
use TTBooking\Accounting\Contracts\Account as AccountContract;
use TTBooking\Accounting\Contracts\AccountOwner;
use TTBooking\Accounting\Contracts\Ledger;
use TTBooking\Accounting\Contracts\TransactionManager;
use TTBooking\Accounting\Exceptions\AccountCreateAbortedException;
use TTBooking\Accounting\Exceptions\AccountNotFoundException;
use TTBooking\Accounting\Models\Account;
use TTBooking\Accounting\Support\AccountPair;
use TTBooking\EntityLocator\Contracts\EntityLocator;
use TTBooking\EntityLocator\Exceptions\EntityNotFoundException;

class AccountManager implements Contracts\AccountManager
{
    /** @var EntityLocator */
    protected EntityLocator $locator;

    /** @var TransactionManager */
    protected TransactionManager $transaction;

    /** @var Ledger */
    protected Ledger $ledger;

    /** @var Repository */
    protected Repository $config;

    /**
     * AccountManager constructor.
     *
     * @param  EntityLocator  $locator
     * @param  TransactionManager  $transaction
     * @param  Ledger  $ledger
     * @param  Repository  $config
     */
    public function __construct(
        EntityLocator $locator,
        TransactionManager $transaction,
        Ledger $ledger,
        Repository $config
    ) {
        $this->locator = $locator;
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
     * @param  AccountOwner  $owner
     * @param  string|null  $type
     * @param  Currency|null  $currency
     * @return Account|Model
     *
     * @throws AccountCreateAbortedException
     */
    public function create(AccountOwner $owner, string $type = null, Currency $currency = null): Account
    {
        return $owner->accounts()->firstOrCreate($this->prepareAttributes(true, $owner, $type, $currency));
    }

    /**
     * Find account by its owner, type and currency.
     *
     * @param  AccountOwner  $owner
     * @param  string|null  $type
     * @param  Currency|null  $currency
     * @return Account|Model
     *
     * @throws AccountCreateAbortedException
     * @throws AccountNotFoundException
     */
    public function find(AccountOwner $owner, string $type = null, Currency $currency = null): Account
    {
        if ($this->config->get('accounting.auto_create_accounts')) {
            return $this->create($owner, $type, $currency);
        }

        try {
            return $owner->accounts()->where($this->prepareAttributes(false, $owner, $type, $currency))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new AccountNotFoundException('Account not found.', $e->getCode(), $e);
        }
    }

    /**
     * Retrieve account by its Universally Unique Identifier (UUID).
     *
     * @param  string  $uuid
     * @return Account|Model
     *
     * @throws AccountNotFoundException
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
     * @param  AccountOwner|null  $owner
     * @return LazyCollection|Account[]
     */
    public function all(AccountOwner $owner = null): LazyCollection
    {
        return is_null($owner) ? Account::query()->cursor() : $owner->accounts()->cursor();
    }

    public function pair(AccountContract $origin, AccountContract $destination): AccountPair
    {
        return new AccountPair($origin, $destination);
    }

    /**
     * Retrieve account by its address.
     *
     * @param  mixed  $address
     * @return Account|object
     *
     * @throws AccountNotFoundException
     */
    public function locate($address): Account
    {
        try {
            return $this->locator->locate(Account::class, $address);
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
     * @param  bool  $forCreate
     * @param  AccountOwner  $owner
     * @param  string|null  $type
     * @param  Currency|null  $currency
     * @return array
     */
    protected function prepareAttributes(bool $forCreate, AccountOwner $owner, string $type = null, Currency $currency = null)
    {
        $currency = isset($currency) ? $currency->getCode() : null;

        $schema = $this->config->get('accounting.account_schema');
        $strict = $this->config->get('accounting.account_schema_strict_mode');

        $possible = array_intersect(
            array_merge([get_class($owner)], class_parents($owner), class_implements($owner)),
            array_keys($schema)
        );

        if ($forCreate && $strict && empty($possible)) {
            throw new AccountCreateAbortedException('Given owner not found in account schema.');
        }

        $constraints = call_user_func_array('array_merge_recursive', array_map(fn ($key) => $schema[$key], $possible));
        $types = isset($constraints['types'])
            ? array_unique(array_filter((array) $constraints['types'])) : [];
        $currencies = isset($constraints['currencies'])
            ? array_unique(array_filter((array) $constraints['currencies'])) : [];

        if ($forCreate && $strict && isset($type) && ! empty($types) && ! in_array($type, $types)) {
            throw new AccountCreateAbortedException('Account type not found in account schema for given owner.');
        }

        if ($forCreate && $strict && isset($currency) && ! empty($currencies) && ! in_array($currency, $currencies)) {
            throw new AccountCreateAbortedException('Account currency not found in account schema for given owner.');
        }

        return [
            'type' => $type ?? $types[0] ?? $this->config->get('accounting.default_account_type'),
            'currency' => new Currency($currency ?? $currencies[0] ?? $this->config->get('accounting.default_account_currency')),
        ];
    }
}
