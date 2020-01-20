<?php

namespace Daniser\Accounting\Factories;

use Daniser\Accounting\Contracts\OwnerFactory;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Exceptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelFactory implements OwnerFactory
{
    public function getOwner(string $type, $id): AccountOwner
    {
        if (! is_subclass_of($type, AccountOwner::class)) {
            throw new Exceptions\OwnerTypeMismatchException("Invalid type: $type cannot be resolved.");
        }

        if (! is_subclass_of($type, Model::class)) {
            throw new Exceptions\OwnerTypeMismatchException("Invalid type: $type cannot be resolved.");
        }

        try {
            return $type::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new Exceptions\OwnerNotFoundException("Model $type with id $id not found.", 0, $e);
        }
    }
}
