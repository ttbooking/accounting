<?php

namespace Daniser\Accounting\Contracts;

use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityException;
use Daniser\EntityResolver\Exceptions\ResolverException;

interface AccountOwnerResolver extends EntityResolver
{
    /**
     * @param string $type
     * @param mixed $id
     *
     * @throws ResolverException
     * @throws EntityException
     *
     * @return AccountOwner
     */
    public function resolve(string $type, $id): AccountOwner;
}
