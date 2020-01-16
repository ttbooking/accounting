<?php

namespace Daniser\Accounting;

use Illuminate\Support\Facades\DB;
use Money\Currency;
use Money\Money;

class Transaction implements Contracts\Transaction
{
    /** @var Ledger $ledger */
    protected Ledger $ledger;

    /** @var Models\Transaction $model */
    protected Models\Transaction $model;

    /**
     * Transaction constructor.
     *
     * @param Ledger $ledger
     * @param Models\Transaction $model
     */
    public function __construct(Ledger $ledger, Models\Transaction $model)
    {
        $this->ledger = $ledger;
        $this->model = $model;
    }

    public function getSource(): Account
    {
        return new Account($this->ledger, $this->model->source);
    }

    public function getDestination(): Account
    {
        return new Account($this->ledger, $this->model->destination);
    }

    public function getAmount(): Money
    {
        return $this->ledger->parseMoney($this->model->amount, $this->getCurrency());
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->model->currency);
    }

    public function getPayload(): ?array
    {
        return $this->model->payload;
    }

    public function getStatus(): int
    {
        return $this->model->status;
    }

    /**
     * Set transaction status.
     *
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->model->update(compact('status'));
    }

    /**
     * @internal
     */
    private function commitInternal(): void
    {
        $this->getSource()->decrement($this->getAmount());
        $this->getDestination()->increment($this->getAmount());
        $this->setStatus(self::STATUS_COMMITTED);
    }

    public function commit(): self
    {
        $this->checkStatus(self::STATUS_STARTED);

        if (false === $this->ledger->fireEvent(new Events\TransactionCommitting($this))) {
            return $this->cancel();
        }

        // Let's try and commit given transaction with configured number of attempts.
        try {
            DB::transaction(fn () => $this->commitInternal(), $this->ledger->getCommitAttempts());
        }

        // On failure we'll enter transaction into appropriate state, fire corresponding event
        // and rethrow higher order exception (except if it's suppressed via one of the listeners).
        catch (\Throwable $e) {
            $this->setStatus(self::STATUS_FAILED);
            if (true !== $this->ledger->fireEvent(new Events\TransactionFailed($this, $e))) {
                throw new Exceptions\TransactionCommitFailedException('Transaction commit has failed.', 0, $e);
            }
        }

        $this->ledger->fireEvent(new Events\TransactionCommitted($this), [], false);

        return $this;
    }

    public function cancel(): self
    {
        $this->checkStatus(self::STATUS_STARTED);

        $this->setStatus(self::STATUS_CANCELED);

        $this->ledger->fireEvent(new Events\TransactionCanceled($this), [], false);

        return $this;
    }

    public function revert(): self
    {
        $this->checkStatus(self::STATUS_COMMITTED);

        if (false === $this->ledger->fireEvent(new Events\TransactionReverting($this))) {
            return $this;
        }

        return $this->getDestination()->transfer($this->getSource(), $this->getAmount(), $this->getPayload());
    }

    public function rollback()
    {
        $this->checkStatus(self::STATUS_COMMITTED);

        // TODO: implement
    }

    /**
     * @param int $status
     *
     * @throws Exceptions\TransactionStatusMismatchException
     */
    protected function checkStatus(int $status): void
    {
        if ($this->getStatus() !== $status) {
            throw new Exceptions\TransactionStatusMismatchException('Incorrect transaction status for this operation.');
        }
    }
}
