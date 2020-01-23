<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions;

interface AccountLocator
{
    /**
     * @param string $address
     *
     * @throws Exceptions\AccountLocatorInvalidAddressFormatException
     * @throws Exceptions\AccountNotFoundException
     *
     * @return Account
     */
    public function locate(string $address): Account;
}
