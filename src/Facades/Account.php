<?php

namespace Daniser\Accounting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Daniser\Accounting\AccountManager::class
 */
class Account extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Daniser\Accounting\Contracts\AccountManager::class;
    }
}
