<?php

namespace Daniser\Accounting\Models;

use Carbon\Carbon;
use Daniser\Accounting\Concerns\HasUuidPrimaryKey;
use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\Transaction as TransactionContract;
use Daniser\Accounting\Events;
use Daniser\Accounting\Exceptions;
use Daniser\Accounting\Facades\Ledger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Money\Currency;
use Money\Money;

/**
 * Class Transaction.
 *
 * @property string $uuid
 * @property string $source_uuid
 * @property string $destination_uuid
 * @property string $amount
 * @property string $currency
 * @property string $st_rate
 * @property string $td_rate
 * @property array|null $payload
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $finished_at
 * @property Account $source
 * @property Account $destination
 */
class Transaction extends Model implements TransactionContract
{
    use HasUuidPrimaryKey;

    protected $table = 'accounting_transactions';

    protected $primaryKey = 'uuid';

    //protected $keyType = 'string';

    //public $incrementing = false;

    const UPDATED_AT = 'finished_at';

    protected $attributes = [
        'status' => self::STATUS_STARTED,
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected $fillable = ['source_uuid', 'destination_uuid', 'amount', 'currency', 'payload', 'status'];

    public function source()
    {
        return $this->belongsTo(Account::class);
    }

    public function destination()
    {
        return $this->belongsTo(Account::class);
    }

    public function getSource(): AccountContract
    {
        return $this->source;
    }

    public function getDestination(): AccountContract
    {
        return $this->destination;
    }

    public function getAmount(): Money
    {
        return Ledger::parseMoney($this->amount, $this->getCurrency());
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->currency);
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set transaction status.
     *
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->update(compact('status'));
    }

    /**
     * @internal
     */
    private function commitInternal(): void
    {
        $this->getSource()->decrementMoney($this->getAmount());
        $this->getDestination()->incrementMoney($this->getAmount());
        $this->setStatus(self::STATUS_COMMITTED);
    }

    public function commit(): self
    {
        $this->checkStatus(self::STATUS_STARTED);

        if (false === Ledger::fireEvent(new Events\TransactionCommitting($this))) {
            return $this->cancel();
        }

        // Let's try and commit given transaction with configured number of attempts.
        try {
            DB::transaction(fn () => $this->commitInternal(), config('accounting.transaction.commit_attempts'));
        }

        // On failure we'll enter transaction into appropriate state, fire corresponding event
        // and rethrow higher order exception (except if it's suppressed via one of the listeners).
        catch (\Throwable $e) {
            $this->setStatus(self::STATUS_FAILED);
            if (true !== Ledger::fireEvent(new Events\TransactionFailed($this, $e))) {
                throw new Exceptions\TransactionCommitFailedException('Transaction commit has failed.', 0, $e);
            }
        }

        Ledger::fireEvent(new Events\TransactionCommitted($this), [], false);

        return $this;
    }

    public function cancel(): self
    {
        $this->checkStatus(self::STATUS_STARTED);

        $this->setStatus(self::STATUS_CANCELED);

        Ledger::fireEvent(new Events\TransactionCanceled($this), [], false);

        return $this;
    }

    public function revert(): self
    {
        $this->checkStatus(self::STATUS_COMMITTED);

        if (false === Ledger::fireEvent(new Events\TransactionReverting($this))) {
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
