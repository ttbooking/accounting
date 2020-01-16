<?php

namespace Daniser\Accounting\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;

/**
 * Class Transaction.
 * @property int $id
 * @property int $source_id
 * @property int $destination_id
 * @property Money $amount
 * @property Currency $currency
 * @property array|null $payload
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $finished_at
 * @property Account $source
 * @property Account $destination
 */
class Transaction extends Model
{
    const STATUS_STARTED = 0;
    const STATUS_COMMITTED = 1;
    const STATUS_CANCELED = 2;
    const STATUS_FAILED = 3;

    const UPDATED_AT = 'finished_at';

    protected $attributes = [
        'status' => self::STATUS_STARTED,
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected $fillable = ['source_id', 'destination_id', 'amount', 'currency', 'payload', 'status'];

    public function getAmountAttribute($amount)
    {
        return new Money($amount, $this->currency);
    }

    public function getCurrencyAttribute($currency)
    {
        return new Currency($currency);
    }

    public function source()
    {
        return $this->belongsTo(Account::class);
    }

    public function destination()
    {
        return $this->belongsTo(Account::class);
    }
}
