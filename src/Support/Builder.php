<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Set the relationships that should be eager loaded and locked.
     *
     * @param bool $lock
     * @param mixed ...$relations
     *
     * @return $this
     */
    public function withLocked($lock = true, ...$relations)
    {
        if (isset($relations[0]) && is_array($relations[0])) {
            $relations = $relations[0];
        }

        $eagerLoad = $this->parseWithRelations($relations);

        $eagerLoad = array_map(fn ($constraints) => fn ($query) => tap($query, $constraints)->lock($lock), $eagerLoad);

        $this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

        return $this;
    }
}
