<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Support\Builder;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait Lockable
{
    /**
     * Eager load relations on the model.
     *
     * @param bool|null $lock
     * @param mixed ...$relations
     *
     * @return $this
     */
    public function loadLocked($lock = true, ...$relations)
    {
        if (isset($relations[0]) && is_array($relations[0])) {
            $relations = $relations[0];
        }

        $query = $this->newQueryWithoutRelationships()->withLocked($lock, $relations);

        $query->eagerLoadRelations([$this]);

        return $this;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param QueryBuilder $query
     *
     * @return Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Reload the current model instance with fresh attributes from the database.
     *
     * @param bool|null $lock
     * @param mixed ...$with
     *
     * @return $this
     */
    public function refresh($lock = null, ...$with)
    {
        if (! $this->exists) {
            return $this;
        }

        if (isset($with[0]) && is_array($with[0])) {
            $with = $with[0];
        }

        $this->setRawAttributes(
            static::newQueryWithoutScopes()->lock($lock)->findOrFail($this->getKey())->attributes
        );

        $this->load(collect($this->relations)->reject(function ($relation) {
            return $relation instanceof Pivot
                || (is_object($relation) && in_array(AsPivot::class, class_uses_recursive($relation), true));
        })->keys()->all());

        $this->loadLocked($lock, $with);

        $this->syncOriginal();

        return $this;
    }

    /**
     * Lock the selected rows in the table for updating.
     *
     * @param mixed ...$with
     *
     * @return $this
     */
    public function refreshForUpdate(...$with)
    {
        return $this->refresh(true, $with);
    }

    /**
     * Share lock the selected rows in the table.
     *
     * @param mixed ...$with
     *
     * @return $this
     */
    public function refreshShared(...$with)
    {
        return $this->refresh(false, $with);
    }
}
