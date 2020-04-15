<?php

namespace Daniser\Accounting\Contracts;

use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityException;
use Daniser\EntityResolver\Exceptions\ResolverException;

interface AccountResolver extends EntityResolver
{
    /**
     * @param string $type
     * @param mixed $id
     *
     * @throws ResolverException
     * @throws EntityException
     *
     * @return Account
     */
    public function resolve(string $type, $id): Account;
}
