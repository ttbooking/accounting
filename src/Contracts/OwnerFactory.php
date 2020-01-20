<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\FactoryException;
use Daniser\Accounting\Exceptions\OwnerException;

// EntityResolver::resolve ?
interface OwnerFactory
{
    /**
     * @param string $type
     * @param mixed $id
     *
     * @throws FactoryException
     * @throws OwnerException
     *
     * @return AccountOwner
     */
    public function getOwner(string $type, $id): AccountOwner;
}
