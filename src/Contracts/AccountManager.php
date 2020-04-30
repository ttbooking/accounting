<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Illuminate\Support\Collection;
use Money\Currency;

interface AccountManager
{
    /**
     * Create new account or retrieve one if it exists.
     *
     * @param AccountOwner $owner
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @return Account
     */
    public function create(AccountOwner $owner, string $type = null, Currency $currency = null): Account;

    /**
     * Find account by its owner, type and currency.
     *
     * @param AccountOwner $owner
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @throws AccountNotFoundException
     *
     * @return Account
     */
    public function find(AccountOwner $owner, string $type = null, Currency $currency = null): Account;

    /**
     * Retrieve account by its Universally Unique Identifier (UUID).
     *
     * @param string $uuid
     *
     * @throws AccountNotFoundException
     *
     * @return Account
     */
    public function get(string $uuid): Account;

    /**
     * Retrieve all existing accounts, or all accounts belonging to a single owner.
     *
     * @param AccountOwner|null $owner
     *
     * @return Collection|Account[]
     */
    public function all(AccountOwner $owner = null): Collection;

    /**
     * Retrieve account by its address.
     *
     * @param mixed $address
     *
     * @throws AccountNotFoundException
     *
     * @return Account
     */
    public function locate($address): Account;

    public function delete(Account $account): void;

    public function purge($onlyBlank = true): void;

    /**
     * Check validity of cached balance for all accounts.
     *
     * @param bool $aggressive
     * @param Collection|null $invalid
     *
     * @return bool
     */
    public function isValid(bool $aggressive = false, Collection $invalid = null): bool;

    /**
     * Fix cached balance for all accounts.
     */
    public function fix(): void;
}
