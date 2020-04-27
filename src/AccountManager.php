<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Models\Account;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Money\Currency;

class AccountManager implements Contracts\AccountManager
{
    /** @var EntityResolver */
    protected EntityResolver $resolver;

    /** @var array */
    protected array $config;

    /**
     * AccountManager constructor.
     *
     * @param EntityResolver $resolver
     * @param array $config
     */
    public function __construct(EntityResolver $resolver, array $config = [])
    {
        $this->resolver = $resolver;
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
