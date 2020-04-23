<?php

namespace Daniser\Accounting\Models;

use Carbon\Carbon;
use Daniser\Accounting\Concerns\HasUuidPrimaryKey;
use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Exceptions\TransactionIdenticalEndpointsException;
use Daniser\Accounting\Facades\Ledger;
use Daniser\EntityResolver\Concerns\Resolvable;
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
 */
class Account extends Model implements AccountContract
{
    use HasUuidPrimaryKey, Resolvable;

    protected $table = 'accounting_accounts';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

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

    public function getAccountKey()
    {
        return $this->getKey();
    }

    public function getOwner(): AccountOwner
    {
        return $this->owner;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->currency);
    }

    public function getBalance(bool $fix = false): Money
    {
        return Ledger::deserializeMoney($this->balance, $this->getCurrency());
    }

    public function isBalanceValid(): bool
    {
        return true;
    }

    public function transferMoney(AccountContract $recipient, $amount, array $payload = null): Transaction
    {
        if ($this->getAccountKey() === $recipient->getAccountKey()) {
            throw new TransactionIdenticalEndpointsException('Transaction endpoints are identical.');
        }

        if (! $amount instanceof Money) {
            $fallbackCurrency = config('accounting.transaction.default_currency');
            if ($fallbackCurrency === 'source') {
                $fallbackCurrency = $this->getCurrency();
            } elseif ($fallbackCurrency === 'destination') {
                $fallbackCurrency = $recipient->getCurrency();
            } else {
                $fallbackCurrency = new Currency($fallbackCurrency);
            }
            $amount = Ledger::parseMoney($amount, $fallbackCurrency);
        }

        return tap(Transaction::create([
            'source_uuid' => $this->getAccountKey(),
            'destination_uuid' => $recipient->getAccountKey(),
            'currency' => $amount->getCurrency()->getCode(),
            'amount' => Ledger::serializeMoney($amount),
            'payload' => $payload,
        ]), function (Transaction $transaction) {
            config('accounting.transaction.auto_commit') && $transaction->commit();
        });
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
