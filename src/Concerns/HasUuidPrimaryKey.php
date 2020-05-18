<?php

namespace Daniser\Accounting\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuidPrimaryKey
{
    public function hasOrderedUuid(): bool
    {
        return (bool) $this->orderedUuid;
    }

    protected static function bootHasUuidPrimaryKey()
    {
        static::creating(function (Model $model) {
            $uuid = $model->hasOrderedUuid()
                ? (string) Str::orderedUuid()
                : (string) Str::uuid();

            $model->setAttribute($model->getKeyName(), $uuid);
        });
    }

    protected function initializeHasUuidPrimaryKey()
    {
        $this->setKeyType('string')->setIncrementing(false);
    }
}
