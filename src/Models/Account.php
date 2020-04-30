<?php

namespace Daniser\Accounting\Models;

use Carbon\Carbon;
use Daniser\Accounting\Concerns\HasUuidPrimaryKey;
use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Exceptions\TransactionCreateAbortedException;
use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Exceptions\TransactionNegativeAmountException;
use Daniser\Accounting\Exceptions\TransactionZeroTransferException;
use Daniser\Accounting\Facades\Ledger;
use Daniser\Accounting\Facades\Transaction as TransactionManager;
use Daniser\EntityResolver\Concerns\Resolvable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Money\Currency;
use Money\Money;

/**
 * Class Account.
 *
 * @property string $uuid
 * @property string $owner_type
 * @property int $owner_id
 * @property string $type
 * @property string $currency
 * @property string $balance
 * @property array|null $context
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Model|AccountOwner $owner
 * @property Collection|Transaction[] $outgoingTransactions
 * @property Collection|Transaction[] $incomingTransactions
 */
class Account extends Model implements AccountContract
{
    use HasUuidPrimaryKey, Resolvable;

    protected $table = 'accounting_accounts';

    protected $primaryKey = 'uuid';

    protected $casts = [
        'context' => 'array',
    ];

    protected $fillable = ['owner_type', 'owner_id', 'type', 'currency', 'balance', 'context'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function outgoingTransactions()
    {
        return $this->hasMany(Transaction::class, 'origin_uuid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function incomingTransactions()
    {
        return $this->hasMany(Transaction::class, 'destination_uuid');
    }

    public function getAccountKey()
    {
        return $this->getKey();
    }

    public function getOwner(): AccountOwner
    {
        return $this->owner;
    }

    public function getAccountType(): string
    {
        return $this->type;
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->currency);
    }

    public function getIncome(DateTimeInterface $byDate = null): Money
    {
        $query = $this->incomingTransactions()->where('status', Transaction::STATUS_COMMITTED);

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return Ledger::deserializeMoney($query->sum('amount'), $this->getCurrency());
    }

    public function getExpense(DateTimeInterface $byDate = null): Money
    {
        $query = $this->outgoingTransactions()->where('status', Transaction::STATUS_COMMITTED);

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return Ledger::deserializeMoney($query->sum('amount'), $this->getCurrency());
    }

    public function getBalance(DateTimeInterface $byDate = null): Money
    {
        if (is_null($byDate)) {
            return Ledger::deserializeMoney($this->balance, $this->getCurrency());
        }

        if ($byDate == new \DateTime) {
            $byDate = null;
        }

        return $this->getIncome($byDate)->subtract($this->getExpense($byDate));
    }

    public function isBalanceValid(): bool
    {
        return $this->getBalance()->equals($this->getBalance(new \DateTime));
    }

    public function fixBalance(): void
    {
        return;
    }

    /**
     * Transfer money to another account.
     *
     * @param AccountContract $destination
     * @param Money $amount
     * @param array|null $payload
     *
     * @throws TransactionIdenticalEndpointsException
     * @throws TransactionZeroTransferException
     * @throws TransactionNegativeAmountException
     * @throws TransactionCreateAbortedException
     *
     * @return Transaction
     */
    public function transferMoney(AccountContract $destination, Money $amount, array $payload = null): Transaction
    {
        return TransactionManager::create($this, $destination, $amount, $payload);
    }

    public function incrementMoney(Money $amount): void
    {
        $amount = Ledger::convertMoney($amount, $this->getCurrency());
        config('accounting.account.use_money_calculator')
            ? $this->update(['balance' => Ledger::serializeMoney($this->getBalance()->add($amount))])
            : $this->increment('balance', Ledger::serializeMoney($amount));
    }

    public function decrementMoney(Money $amount): void
    {
        $amount = Ledger::convertMoney($amount, $this->getCurrency());
        config('accounting.account.use_money_calculator')
            ? $this->update(['balance' => Ledger::serializeMoney($this->getBalance()->subtract($amount))])
            : $this->decrement('balance', Ledger::serializeMoney($amount));
    }

    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (\Exception $e) {
            if ($method !== $action = Str::replaceLast('Money', '', $method)) {
                /**
                 * @var Money
                 * @var array|null $payload
                 */
                [$amount, $payload] = $parameters + [1 => null];

                if (! Str::startsWith($action, 'un')) {
                    return $this->transferMoney(
                        $this->getOwner()->getAccount("{$action}ed", $amount->getCurrency()),
                        $amount, $payload
                    );
                } else {
                    $action = Str::substr($action, 2);

                    return $this->getOwner()->getAccount("{$action}ed", $amount->getCurrency())
                        ->transferMoney($this, $amount, $payload);
                }
            } else {
                throw $e;
            }
        }
    }
}
