<?php

namespace Daniser\Accounting\Concerns;

use Daniser\EntityResolver\Exceptions\EntityException;
use Daniser\EntityResolver\Exceptions\ResolverException;
use Daniser\EntityResolver\Facades\EntityResolver;

trait Resolvable
{
    /**
     * @param mixed $address
     *
     * @throws ResolverException
     * @throws EntityException
     *
     * @return static
     */
    public static function resolve($address)
    {
        return is_a($address, __CLASS__) ? $address : EntityResolver::resolve(__CLASS__, $address);
    }
}
