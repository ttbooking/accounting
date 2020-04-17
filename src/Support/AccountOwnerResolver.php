<?php

namespace Daniser\Accounting\Support;

use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityTypeMismatchException;

class AccountOwnerResolver implements EntityResolver
{
    /** @var EntityResolver */
    protected EntityResolver $resolver;

    /**
     * AccountOwnerResolver constructor.
     *
     * @param EntityResolver $resolver
     */
    public function __construct(EntityResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function resolve(string $type, $id): AccountOwner
    {
        $owner = $this->resolver->resolve($type, $id);

        if (! $owner instanceof AccountOwner) {
            throw new EntityTypeMismatchException("Invalid type: $type does not implement AccountOwner contract.");
        }

        return $owner;
    }
}
