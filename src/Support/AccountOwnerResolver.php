<?php

namespace Daniser\Accounting\Support;

use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\Accounting\Contracts;
use Daniser\EntityResolver\Exceptions\EntityTypeMismatchException;

class AccountOwnerResolver implements Contracts\AccountOwnerResolver
{
    /** @var EntityResolver $resolver */
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

    public function resolve(string $type, $id): Contracts\AccountOwner
    {
        $owner = $this->resolver->resolve($type, $id);

        if (! $owner instanceof Contracts\AccountOwner) {
            throw new EntityTypeMismatchException("Invalid type: $type does not implement AccountOwner contract.");
        }

        return $owner;
    }
}
