<?php

namespace Daniser\Accounting\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuidPrimaryKey
{
    protected static function bootHasUuidPrimaryKey()
    {
        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), $model->orderedUuid ? Str::orderedUuid() : Str::uuid());
        });
    }
}
