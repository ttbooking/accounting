<?php

namespace Daniser\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Money\Currency;
use Money\Money;
use Carbon\Carbon;

/**
 * Class Account
 * @package Daniser\Accounting\Models
 * @property int $id
 * @property int $owner_id
 * @property string $type
 * @property Currency $currency
 * @property Money $balance
 * @property Money $limit
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Model $owner
 */
class Account extends Model
{
    /**
     * @param int $ownerId
     * @param string $type
     * @param string $currency
     *
     * @throws ModelNotFoundException
     *
     * @return static|Model
     */
    public static function findByRequisites($ownerId, $type, $currency): self
    {
        return static::query()
            ->where('owner_id', $ownerId)
            ->where('type', $type)
            ->where('currency', $currency)
            ->firstOrFail();
    }

    public function getCurrencyAttribute($currency)
    {
        return new Currency($currency);
    }

    public function getBalanceAttribute($balance)
    {
        return new Money($balance, $this->currency);
    }

    public function getLimitAttribute($limit)
    {
        return new Money($limit, $this->currency);
    }

    public function owner()
    {
        return $this->morphTo();
    }
}
