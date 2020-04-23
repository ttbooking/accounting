<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use Money\Money;

interface TransactionManager
{
    /**
     * Create new transaction.
     *
     * @param Account|string $origin
     * @param Account|string $destination
     * @param Money|string $amount
     * @param array|null $payload
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     *
     * @return Transaction
     */
    public function create($origin, $destination, $amount, array $payload = null): Transaction;

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
}
