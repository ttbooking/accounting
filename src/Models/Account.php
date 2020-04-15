<?php

namespace Daniser\Accounting\Models;

use Carbon\Carbon;
use Daniser\Accounting\Concerns\HasUuidPrimaryKey;
use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Facades\Ledger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    use HasUuidPrimaryKey;

    protected $table = 'accounting_accounts';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'context' => 'array',
    ];

    protected $fillable = ['owner_type', 'owner_id', 'type', 'currency', 'balance', 'context'];

    /**
     * @param string $ownerType
     * @param int $ownerId
     * @param string $type
     * @param string $currency
     *
     * @throws ModelNotFoundException
     *
     * @return static|Model
     */
    public static function findByRequisites($ownerType, $ownerId, $type, $currency): self
    {
        return static::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('type', $type)
            ->where('currency', $currency)
            ->firstOrFail();
    }

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
        return Ledger::parseMoney($this->balance, $this->getCurrency());
    }

    public function isBalanceValid(): bool
    {
        return true;
    }

    public function transferMoney(AccountContract $recipient, Money $amount, array $payload = null): Transaction
    {
        return tap(Transaction::create([
            'source_uuid' => $this->getAccountKey(),
            'destination_uuid' => $recipient->getAccountKey(),
            'currency' => $amount->getCurrency()->getCode(),
            'amount' => Ledger::formatMoney($amount),
            'payload' => $payload,
        ]), function (Transaction $transaction) {
            config('accounting.transaction.auto_commit') && $transaction->commit();
        });
    }

    public function incrementMoney(Money $amount): void
    {
        $amount = Ledger::convertMoney($amount, $this->getCurrency());
        config('accounting.account.use_money_calculator')
            ? $this->update(['balance' => Ledger::formatMoney($this->getBalance()->add($amount))])
            : $this->increment('balance', Ledger::formatMoney($amount));
    }

    public function decrementMoney(Money $amount): void
    {
        $amount = Ledger::convertMoney($amount, $this->getCurrency());
        config('accounting.account.use_money_calculator')
            ? $this->update(['balance' => Ledger::formatMoney($this->getBalance()->subtract($amount))])
            : $this->decrement('balance', Ledger::formatMoney($amount));
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
