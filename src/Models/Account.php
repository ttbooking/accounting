<?php

namespace Daniser\Accounting\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class Account.
 * @property int $id
 * @property int $owner_id
 * @property string $type
 * @property string $currency
 * @property string $balance
 * @property string $limit
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Model $owner
 */
class Account extends Model
{
    protected $fillable = ['owner_id', 'type', 'currency', 'balance', 'limit'];

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

    public function owner()
    {
        return $this->morphTo();
    }
}
