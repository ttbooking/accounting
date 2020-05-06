<?php

namespace Daniser\Accounting\Models;

use Carbon\Carbon;
use Closure;
use Daniser\Accounting\Concerns\HasUuidPrimaryKey;
use Daniser\Accounting\Concerns\Lockable;
use Daniser\Accounting\Contracts\Transaction as TransactionContract;
use Daniser\Accounting\Events;
use Daniser\Accounting\Exceptions;
use Daniser\Accounting\Facades\Ledger;
use Daniser\Accounting\Facades\Transaction as TransactionManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;
use Throwable;

/**
 * Class Transaction.
 *
 * @property string $uuid
 * @property string $parent_uuid
 * @property string $origin_uuid
 * @property string $destination_uuid
 * @property string $currency
 * @property string $ot_rate
 * @property string $td_rate
 * @property string $amount
 * @property array|null $payload
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $finished_at
 * @property Transaction|null $parent
 * @property Collection|Transaction[] $children
 * @property Account $origin
 * @property Account $destination
 */
class Transaction extends Model implements TransactionContract
{
    use HasUuidPrimaryKey, Lockable;

    protected $table = 'accounting_transactions';

    protected $primaryKey = 'uuid';

    protected $orderedUuid = true;

    const CREATED_AT = 'started_at';

    const UPDATED_AT = 'finished_at';

    protected $attributes = [
        'status' => self::STATUS_STARTED,
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected $fillable = [
        'parent_uuid',
        'origin_uuid',
        'destination_uuid',
        'currency',
        'ot_rate',
        'td_rate',
        'amount',
        'payload',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $transaction) {
            return Ledger::fireEvent(new Events\TransactionCreating($transaction));
        });

        static::created(function (self $transaction) {
            Ledger::fireEvent(new Events\TransactionCreated($transaction), [], false);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(__CLASS__);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(__CLASS__, 'parent_uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function origin()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destination()
    {
        return $this->belongsTo(Account::class);
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getOrigin(): Account
    {
        return $this->origin;
    }

    public function getDestination(): Account
    {
        return $this->destination;
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->currency);
    }

    public function getAmount(): Money
    {
        return Ledger::deserializeMoney($this->amount, $this->getCurrency());
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getRevertedAmount(): Money
    {
        $revertedAmount = $this->children()->where('status', self::STATUS_COMMITTED)->sum('amount');

        return Ledger::deserializeMoney($revertedAmount, $this->getCurrency());
    }

    public function getRemainingAmount(): Money
    {
        return $this->getAmount()->subtract($this->getRevertedAmount());
    }

    public function isReverted(): bool
    {
        return $this->getRevertedAmount()->equals($this->getAmount());
    }

    public function isRevertTransaction(): bool
    {
        return $this->parent()->exists();
    }

    public function commit(): self
    {
        return $this->transact(function () {
            $this->refreshForUpdate('origin', 'destination');
            $this->checkStatus(self::STATUS_STARTED);

            if (false !== Ledger::fireEvent(new Events\TransactionCommitting($this))) {
                $this->checkStatus(self::STATUS_STARTED);
                $this->getOrigin()->decrementMoney($this->getAmount());
                $this->getDestination()->incrementMoney($this->getAmount());
                $this->setStatus(self::STATUS_COMMITTED);
            } else {
                $this->setStatus(self::STATUS_CANCELED);
            }
        });
    }

    public function cancel(): self
    {
        return $this->transact(function () {
            $this->refreshForUpdate();
            $this->checkStatus(self::STATUS_STARTED);
            $this->setStatus(self::STATUS_CANCELED);
        });
    }

    public function revert(Money $amount = null): self
    {
        return $this->transact(function () use ($amount) {
            $this->refreshForUpdate();
            $this->checkStatus(self::STATUS_COMMITTED);

            if (false !== Ledger::fireEvent(new Events\TransactionReverting($this))) {
                return TransactionManager::create(
                    $this->getDestination(),
                    $this->getOrigin(),
                    $amount ?? $this->getRemainingAmount(),
                    $this->getPayload(),
                    $this
                );
            }

            throw new Exceptions\TransactionCreateAbortedException('Reverting transaction creation aborted.');
        });
    }

    /**
     * @param Closure $callback
     *
     * @return mixed
     */
    protected function transact(Closure $callback)
    {
        try {
            $result = Ledger::transaction($callback);
        } catch (Exceptions\TransactionException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->verifyStatus($e);
        }

        $this->verifyStatus();

        return $result ?? $this;
    }

    /**
     * Set transaction status.
     *
     * @param int $status
     */
    protected function setStatus(int $status): void
    {
        $this->update(compact('status'));
    }

    /**
     * Check transaction status before operation.
     *
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

    /**
     * Verify transaction status after operation.
     *
     * @param Throwable|null $e
     *
     * @throws Exceptions\TransactionException
     */
    protected function verifyStatus(Throwable $e = null): void
    {
        [ , , $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $operation = $caller['function'];

        switch ($this->getStatus()) {

            case self::STATUS_STARTED:
                if (true !== Ledger::fireEvent(new Events\TransactionFailed($this, $e))) {
                    $code = is_null($e) ? 0 : $e->getCode();
                    throw new Exceptions\TransactionFailedException("Transaction {$operation} has failed.", $code, $e);
                }
                break;

            case self::STATUS_COMMITTED:
                Ledger::fireEvent(new Events\TransactionCommitted($this), [], false);
                break;

            case self::STATUS_CANCELED:
                Ledger::fireEvent(new Events\TransactionCanceled($this), [], false);
                break;

            default:
                throw new Exceptions\TransactionStatusMismatchException('Unknown transaction status.');
        }
    }
}
