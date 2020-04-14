<?php

namespace Daniser\Accounting\Concerns;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

trait HasUuidPrimaryKey
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function bootHasUuidPrimaryKey()
    {
        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
