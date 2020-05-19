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

    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function (Model $model) {
            $uuid = $model->hasOrderedUuid()
                ? (string) Str::orderedUuid()
                : (string) Str::uuid();

            $model->setAttribute($model->getKeyName(), $uuid);
        });
    }

    protected function initializeHasUuidPrimaryKey(): void
    {
        $this->setKeyType('string')->setIncrementing(false);
    }
}
