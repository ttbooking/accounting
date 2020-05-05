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

    public function getKey();

    public function getParent(): ?self;

    public function getChild(): ?self;

    public function getOrigin(): Account;

    public function getDestination(): Account;

    public function getCurrency(): Currency;

    public function getAmount(): Money;

    public function getPayload(): ?array;

    public function getStatus(): int;

    public function isReverted(): bool;

    public function isRevertTransaction(): bool;

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
}
