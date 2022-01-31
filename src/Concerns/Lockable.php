<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Concerns;

use Illuminate\Database\Query\Builder as QueryBuilder;
use TTBooking\Accounting\Support\Builder;

trait Lockable
{
    /**
     * Eager load relations on the model and lock them.
     *
     * @param  bool  $lock
     * @param  mixed  ...$relations
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
     * Create a new custom Eloquent query builder for the model.
     *
     * @param  QueryBuilder  $query
     * @return Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Reload the current model instance with fresh attributes from the database and lock it and its chosen relations.
     *
     * @param  bool  $lock
     * @param  mixed  ...$with
     * @return $this
     */
    public function refreshLocked($lock = true, ...$with)
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

        $this->loadLocked($lock, $with);

        $this->syncOriginal();

        return $this;
    }

    /**
     * Reload the current model and its chosen relations and lock them for updating.
     *
     * @param  mixed  ...$with
     * @return $this
     */
    public function refreshForUpdate(...$with)
    {
        return $this->refreshLocked(true, $with);
    }

    /**
     * Reload and share lock the current model and its chosen relations.
     *
     * @param  mixed  ...$with
     * @return $this
     */
    public function refreshShared(...$with)
    {
        return $this->refreshLocked(false, $with);
    }
}
