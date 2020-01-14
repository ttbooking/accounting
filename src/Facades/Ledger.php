<?php

namespace Daniser\Accounting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Daniser\Accounting\Ledger::class
 */
class Ledger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Daniser\Accounting\Contracts\Ledger::class;
    }
}
