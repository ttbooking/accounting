<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Money\Currency;
use Money\Money;
use TTBooking\Accounting\Contracts\Account as AccountContract;
use TTBooking\Accounting\Contracts\AccountOwner;
use TTBooking\Accounting\Exceptions;
use TTBooking\Accounting\Facades\Ledger;
use TTBooking\Accounting\Facades\Transaction as TransactionManager;
use TTBooking\CastableMoney\Casts\Currency as CurrencyCast;
use TTBooking\CastableMoney\Casts\DecimalMoney;
use TTBooking\EntityLocator\Concerns\Locatable;
use TTBooking\ModelExtensions\Concerns\HasConfigurableName;
use TTBooking\ModelExtensions\Concerns\HasUuidPrimaryKey;

/**
 * Class Account.
 *
 * @property string $uuid
 * @property string $owner_type
 * @property int $owner_id
 * @property string $type
 * @property Currency $currency
 * @property Money $balance
 * @property array|null $context
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Model|AccountOwner $owner
 * @property Collection|Transaction[] $outgoingTransactions
 * @property Collection|Transaction[] $incomingTransactions
 */
class Account extends Model implements AccountContract
{
    use HasConfigurableName, HasUuidPrimaryKey, Locatable;

    protected $table = 'accounting_accounts';

    protected $primaryKey = 'uuid';

    protected $casts = [
        'currency' => CurrencyCast::class,
        'balance' => DecimalMoney::class,
        'context' => 'array',
    ];

    protected $fillable = ['owner_type', 'owner_id', 'type', 'currency', 'balance', 'context'];

    protected static function booted()
    {
        static::creating(function (self $account) {
            if (false === Ledger::fireEvent($account->buildEvent('creating'), [$account])) {
                throw new Exceptions\AccountCreateAbortedException('Account creation aborted by event listener.');
            }
        });

        static::created(function (self $account) {
            Ledger::fireEvent($account->buildEvent('created'), [$account], false);
        });
    }

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
        return $this->currency;
    }

    public function getIncome(DateTimeInterface $byDate = null, AccountContract $origin = null): Money
    {
        $query = $this->incomingTransactions()->where('status', Transaction::STATUS_COMMITTED);

        if (! is_null($origin)) {
            $query->where('origin_uuid', $origin->getAccountKey());
        }

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return Ledger::deserializeMoney($query->sum('destination_amount'), $this->getCurrency());
    }

    public function getExpense(DateTimeInterface $byDate = null, AccountContract $destination = null): Money
    {
        $query = $this->outgoingTransactions()->where('status', Transaction::STATUS_COMMITTED);

        if (! is_null($destination)) {
            $query->where('destination_uuid', $destination->getAccountKey());
        }

        if (! is_null($byDate)) {
            $query->where('finished_at', '<=', $byDate);
        }

        return Ledger::deserializeMoney($query->sum('origin_amount'), $this->getCurrency());
    }

    public function getBalance(DateTimeInterface $byDate = null, AccountContract $other = null): Money
    {
        if (is_null($byDate) && is_null($other)) {
            return $this->balance;
        }

        if ($byDate == new \DateTime) {
            $byDate = null;
        }

        return $this->getIncome($byDate, $other)->subtract($this->getExpense($byDate, $other));
    }

    public function getContext(string $key = null, $default = null)
    {
        return data_get($this->context, $key, $default);
    }

    public function isBalanceValid(): bool
    {
        return $this->getBalance()->equals($this->getBalance(new \DateTime));
    }

    public function fixBalance(): void
    {
    }

    /**
     * Transfer money to another account.
     *
     * @param AccountContract $destination
     * @param Money $amount
     * @param array|null $payload
     *
     * @throws Exceptions\TransactionIdenticalEndpointsException
     * @throws Exceptions\TransactionZeroTransferException
     * @throws Exceptions\TransactionNegativeAmountException
     * @throws Exceptions\TransactionCreateAbortedException
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
        Config::get('accounting.prefer_money_calculator')
            ? $this->update(['balance' => $this->getBalance()->add($amount)])
            : $this->increment('balance', Ledger::serializeMoney($amount));
    }

    public function decrementMoney(Money $amount): void
    {
        $amount = Ledger::convertMoney($amount, $this->getCurrency());
        Config::get('accounting.prefer_money_calculator')
            ? $this->update(['balance' => $this->getBalance()->subtract($amount)])
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

    /**
     * @param string $event
     *
     * @return string
     */
    protected function buildEvent(string $event): string
    {
        // For example, "account.creating.user.default"
        return implode('.', ['account', $event, $this->owner_type, $this->type]);
    }
}
