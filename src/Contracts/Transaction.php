<?php

namespace TTBooking\Accounting\Contracts;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Money\Currency;
use Money\Money;
use TTBooking\Accounting\Exceptions\TransactionException;

interface Transaction extends Jsonable
{
    const STATUS_STARTED = 0;
    const STATUS_COMMITTED = 1;
    const STATUS_CANCELED = 2;

    public function getKey();

    public function getParent(): ?self;

    public function getChildren(): Collection;

    public function getOrigin(): Account;

    public function getDestination(): Account;

    public function getCurrency(): Currency;

    public function getAmount(): Money;

    public function getBaseAmount(): Money;

    public function getOriginAmount(): Money;

    public function getDestinationAmount(): Money;

    public function getPayload(): ?array;

    public function getStatus(): int;

    public function getDigest(): string;

    public function updateDigest(): void;

    public function getRevertedAmount(): Money;

    public function getRemainingAmount(): Money;

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
     * @param Money|null $amount
     *
     * @throws TransactionException
     *
     * @return static
     */
    public function revert(Money $amount = null): self;
}
