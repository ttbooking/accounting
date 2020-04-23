<?php

namespace Daniser\Accounting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Daniser\Accounting\TransactionManager::class
 */
class Transaction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Daniser\Accounting\Contracts\TransactionManager::class;
    }
}
