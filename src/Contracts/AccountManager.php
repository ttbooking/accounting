<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Money\Currency;
use Money\Money;
use TTBooking\Accounting\Exceptions\AccountCreateAbortedException;
use TTBooking\Accounting\Exceptions\AccountNotFoundException;

interface AccountManager
{
    /**
     * Get table name for account storage.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Create new account or retrieve one if it exists.
     *
     * @param  AccountOwner  $owner
     * @param  string|null  $type
     * @param  Currency|null  $currency
     * @return Account
     *
     * @throws AccountCreateAbortedException
     */
    public function create(AccountOwner $owner, string $type = null, Currency $currency = null): Account;

    /**
     * Find account by its owner, type and currency.
     *
     * @param  AccountOwner  $owner
     * @param  string|null  $type
     * @param  Currency|null  $currency
     * @return Account
     *
     * @throws AccountNotFoundException
     */
    public function find(AccountOwner $owner, string $type = null, Currency $currency = null): Account;

    /**
     * Retrieve account by its Universally Unique Identifier (UUID).
     *
     * @param  string  $uuid
     * @return Account
     *
     * @throws AccountCreateAbortedException
     * @throws AccountNotFoundException
     */
    public function get(string $uuid): Account;

    /**
     * Retrieve all existing accounts, or all accounts belonging to a single owner.
     *
     * @param  AccountOwner|null  $owner
     * @return Enumerable|Account[]
     */
    public function all(AccountOwner $owner = null): Enumerable;

    /**
     * @param  Account  $origin
     * @param  Account  $destination
     * @return AccountPair
     */
    public function pair(Account $origin, Account $destination): AccountPair;

    /**
     * Retrieve account by its address.
     *
     * @param  mixed  $address
     * @return Account
     *
     * @throws AccountNotFoundException
     */
    public function locate($address): Account;

    public function delete(Account $account): void;

    public function purge($onlyBlank = true): void;

    /**
     * Money balance per account.
     *
     * @return Collection|Money[]
     */
    public function totalPerAccount(): Collection;

    /**
     * Money balance per account (for accounts which failed validation).
     *
     * @return Collection|Money[]
     */
    public function invalidTotalPerAccount(): Collection;

    /**
     * Check validity of cached balance for all accounts.
     *
     * @param  bool  $aggressive
     * @return bool
     */
    public function isValid(bool $aggressive = false): bool;

    /**
     * Fix cached balance for all accounts.
     */
    public function fix(): void;
}
