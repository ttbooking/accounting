<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Models;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;
use Throwable;
use TTBooking\Accounting\Concerns\Lockable;
use TTBooking\Accounting\Contracts\Transaction as TransactionContract;
use TTBooking\Accounting\Events;
use TTBooking\Accounting\Exceptions;
use TTBooking\Accounting\Facades\Ledger;
use TTBooking\Accounting\Facades\Transaction as TransactionManager;
use TTBooking\ModelExtensions\Concerns\HasConfigurableName;
use TTBooking\ModelExtensions\Concerns\HasUuidPrimaryKey;

/**
 * Class Transaction.
 *
 * @property string $uuid
 * @property string $parent_uuid
 * @property string $origin_uuid
 * @property string $destination_uuid
 * @property string $currency
 * @property string $amount
 * @property string|null $base_amount
 * @property string|null $origin_amount
 * @property string|null $destination_amount
 * @property array|null $payload
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $finished_at
 * @property string $digest
 * @property Transaction|null $parent
 * @property Collection|Transaction[] $children
 * @property Account $origin
 * @property Account $destination
 *
 * @method static Builder withStatus(int $status, string $direction = 'asc')
 * @method static Builder uncommitted(string $direction = 'asc')
 * @method static Builder committed(string $direction = 'asc')
 * @method static Builder canceled(string $direction = 'asc')
 * @method static Builder revertable(string $direction = 'asc')
 */
class Transaction extends Model implements TransactionContract
{
    use HasConfigurableName, HasUuidPrimaryKey, Lockable;

    protected $table = 'accounting_transactions';

    protected $primaryKey = 'uuid';

    protected $orderedUuid = true;

    const CREATED_AT = 'started_at';

    const UPDATED_AT = null;

    protected $attributes = [
        'status' => self::STATUS_STARTED,
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected $dates = ['finished_at'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $hidden = [
        'digest',
        'parent',
        'children',
        'origin',
        'destination',
    ];

    protected $fillable = [
        'parent_uuid',
        'origin_uuid',
        'destination_uuid',
        'currency',
        'amount',
        'origin_amount',
        'payload',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $transaction) {
            if ($transaction->status !== self::STATUS_STARTED) {
                throw new Exceptions\TransactionCreateAbortedException("Can't create transaction in finished state.");
            }

            if (false === Ledger::fireEvent(new Events\TransactionCreating($transaction))) {
                throw new Exceptions\TransactionCreateAbortedException('Transaction creation aborted by event listener.');
            }
        });

        static::created(function (self $transaction) {
            Ledger::fireEvent(new Events\TransactionCreated($transaction), [], false);
        });

        static::updating(function (self $transaction) {
            if (array_keys($transaction->getDirty()) !== ['digest']) {
                if ($transaction->getOriginal('status') !== self::STATUS_STARTED) {
                    throw new Exceptions\TransactionUpdateAbortedException("Can't update already finished transaction.");
                }

                if ($transaction->status === self::STATUS_STARTED) {
                    throw new Exceptions\TransactionUpdateAbortedException('Status must be changed during update.');
                }

                $transaction->fixAmounts();
                $transaction->finished_at = $transaction->freshTimestamp();
            }

            if ($transaction->status === self::STATUS_COMMITTED && is_null($transaction->digest)) {
                $transaction->digest = TransactionManager::digest($transaction,
                    static::committed('desc')->where('finished_at', '<', $transaction->finished_at)->value('digest')
                );
            }
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

    /**
     * Scope all transactions with given status.
     *
     * @param Builder $query
     * @param int $status
     * @param string $direction
     *
     * @return Builder
     */
    public function scopeWithStatus(Builder $query, int $status, string $direction = 'asc')
    {
        return $query->where('status', $status)->orderBy($this->getKeyName(), $direction);
    }

    /**
     * Scope all uncommitted transactions.
     *
     * @param Builder $query
     * @param string $direction
     *
     * @return Builder
     */
    public function scopeUncommitted(Builder $query, string $direction = 'asc')
    {
        return $this->scopeWithStatus($query, self::STATUS_STARTED, $direction);
    }

    /**
     * Scope all committed transactions.
     *
     * @param Builder $query
     * @param string $direction
     *
     * @return Builder
     */
    public function scopeCommitted(Builder $query, string $direction = 'asc')
    {
        return $query->where('status', self::STATUS_COMMITTED)->orderBy('finished_at', $direction);
    }

    /**
     * Scope all canceled transactions.
     *
     * @param Builder $query
     * @param string $direction
     *
     * @return Builder
     */
    public function scopeCanceled(Builder $query, string $direction = 'asc')
    {
        return $this->scopeWithStatus($query, self::STATUS_CANCELED, $direction);
    }

    /**
     * Scope all revertable transactions.
     *
     * @param Builder $query
     * @param string $direction
     *
     * @return Builder
     */
    public function scopeRevertable(Builder $query, string $direction = 'asc')
    {
        return $this->scopeCommitted($query, $direction)->whereDoesntHave('children');
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

    public function getBaseAmount(): Money
    {
        $baseCurrency = TransactionManager::baseCurrency();

        if (is_null($this->base_amount)) {
            return Ledger::convertMoney($this->getAmount(), $baseCurrency);
        }

        return Ledger::deserializeMoney($this->base_amount, $baseCurrency);
    }

    public function getOriginAmount(): Money
    {
        $originCurrency = $this->getOrigin()->getCurrency();

        if (is_null($this->origin_amount)) {
            return Ledger::convertMoney($this->getAmount(), $originCurrency);
        }

        return Ledger::deserializeMoney($this->origin_amount, $originCurrency);
    }

    public function getDestinationAmount(): Money
    {
        $destinationCurrency = $this->getDestination()->getCurrency();

        if (is_null($this->destination_amount)) {
            return Ledger::convertMoney($this->getAmount(), $destinationCurrency);
        }

        return Ledger::deserializeMoney($this->destination_amount, $destinationCurrency);
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDigest(): string
    {
        return $this->digest;
    }

    public function updateDigest(): void
    {
        $this->checkStatus(self::STATUS_COMMITTED);
        $this->digest = null;
        $this->save();
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
            static::uncommitted()->lockForUpdate()->get();
            $this->refreshForUpdate('origin', 'destination');
            $this->fixAmounts();
            $this->checkStatus(self::STATUS_STARTED);

            if (false !== Ledger::fireEvent(new Events\TransactionCommitting($this))) {
                $this->checkStatus(self::STATUS_STARTED);
                $this->getOrigin()->decrementMoney($this->getOriginAmount());
                $this->getDestination()->incrementMoney($this->getDestinationAmount());
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

    protected function fixAmounts(): void
    {
        $this->fixBaseAmount();
        $this->fixOriginAmount();
        $this->fixDestinationAmount();
    }

    protected function fixBaseAmount(): void
    {
        if (is_null($this->base_amount)) {
            $this->base_amount = Ledger::serializeMoney($this->getBaseAmount());
        }
    }

    protected function fixOriginAmount(): void
    {
        if (is_null($this->origin_amount)) {
            $this->origin_amount = Ledger::serializeMoney($this->getOriginAmount());
        }
    }

    protected function fixDestinationAmount(): void
    {
        if (is_null($this->destination_amount)) {
            $this->destination_amount = Ledger::serializeMoney($this->getDestinationAmount());
        }
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
        $this->status = $status;
        $this->save();
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
        if ($this->status !== $status) {
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

        switch ($this->status) {

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
