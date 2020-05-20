<?php

namespace Daniser\Accounting\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Facade;
use Daniser\Accounting\Contracts\Account as AccountContract;
use Daniser\Accounting\Contracts\AccountOwner;
use Money\Currency;
use Money\Money;

/**
 * @method static string getTable()
 * @method static AccountContract create(AccountOwner $owner, string $type = null, Currency $currency = null)
 * @method static AccountContract find(AccountOwner $owner, string $type = null, Currency $currency = null)
 * @method static AccountContract get(string $uuid)
 * @method static Enumerable|AccountContract[] all(AccountOwner $owner = null)
 * @method static AccountContract locate(mixed $address)
 * @method static Collection|Money[] totalPerAccount()
 * @method static Collection|Money[] invalidTotalPerAccount()
 * @method static bool isValid(bool $aggressive = false)
 *
 * @see \Daniser\Accounting\AccountManager::class
 */
class Account extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Daniser\Accounting\Contracts\AccountManager::class;
    }
}
