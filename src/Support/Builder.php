<?php

namespace Daniser\Accounting\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Set the relationships that should be eager loaded.
     *
     * @param bool|null $lock
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

        array_walk($eagerLoad, fn ($constraints) => fn ($query) => tap($query, $constraints)->lock($lock));

        $this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

        return $this;
    }
}
