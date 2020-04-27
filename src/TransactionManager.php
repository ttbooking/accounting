<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\AccountManager;
use Daniser\Accounting\Contracts\Ledger;
use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use Daniser\Accounting\Models\Transaction;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Money\Currency;
use Money\Money;

class TransactionManager implements Contracts\TransactionManager
{
    /** @var AccountManager */
    protected AccountManager $account;

    /** @var Ledger */
    protected Ledger $ledger;

    /** @var EntityResolver */
    protected EntityResolver $resolver;

    /** @var array */
    protected array $config;

    /**
     * TransactionManager constructor.
     *
     * @param AccountManager $account
     * @param Ledger $ledger
     * @param EntityResolver $resolver
     * @param array $config
     */
    public function __construct(AccountManager $account, Ledger $ledger, EntityResolver $resolver, array $config = [])
    {
        $this->account = $account;
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

        return $$currency instanceof Account
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
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     *
     * @return Transaction|Model
     */
    public function create(Account $origin, Account $destination, Money $amount, array $payload = null): Transaction
    {
        if ($origin->getAccountKey() === $destination->getAccountKey()) {
            throw new TransactionIdenticalEndpointsException('Transaction endpoints are identical.');
        }

        if (! $this->config['allow_zero_transfers'] && $amount->isZero()) {
            throw new TransactionZeroTransferException('Transaction of zero amount is forbidden.');
        }

        return tap(Transaction::query()->create([
            'origin_uuid' => $origin->getAccountKey(),
            'destination_uuid' => $destination->getAccountKey(),
            'currency' => $amount->getCurrency()->getCode(),
            'amount' => $this->ledger->serializeMoney($amount),
            'payload' => $payload,
        ]), function (Transaction $transaction) {
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
}
