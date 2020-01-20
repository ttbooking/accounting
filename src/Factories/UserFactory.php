<?php

namespace Daniser\Accounting\Factories;

use App\User;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Exceptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserFactory extends ModelFactory
{
    public function getOwner(string $type, $id): AccountOwner
    {
        if (! is_a($type, User::class, true)) {
            throw new Exceptions\OwnerTypeMismatchException("Invalid type: $type cannot be resolved.");
        }

        if (is_string($id) && ! ctype_digit($id)) {
            try {
                return $type::where('email', $id)->firstOrFail();
            } catch (ModelNotFoundException $e) {
                throw new Exceptions\OwnerNotFoundException("User with email $id not found.", 0, $e);
            }
        }

        return parent::getOwner($type, $id);
    }
}
