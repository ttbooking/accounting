<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\TransactionException;
use Money\Currency;
use Money\Money;

interface Transaction
{
    const STATUS_STARTED = 0;
    const STATUS_COMMITTED = 1;
    const STATUS_CANCELED = 2;
    const STATUS_FAILED = 3;

    public function getOrigin(): Account;

    public function getDestination(): Account;

    public function getCurrency(): Currency;

    public function getAmount(): Money;

    public function getPayload(): ?array;

    public function getStatus(): int;

    /**
     * @throws TransactionException
     *
     * @return $this
     */
    public function commit(): self;

    /**
     * @return $this
     */
    public function cancel(): self;

    /**
     * @return static|$this
     */
    public function revert(): self;

    public function rollback();
}
