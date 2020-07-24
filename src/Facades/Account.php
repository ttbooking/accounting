<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Facade;
use Money\Currency;
use Money\Money;
use TTBooking\Accounting\Contracts\Account as AccountContract;
use TTBooking\Accounting\Contracts\AccountOwner;
use TTBooking\Accounting\Contracts\AccountPair;

/**
 * @method static string getTable()
 * @method static AccountContract create(AccountOwner $owner, string $type = null, Currency $currency = null)
 * @method static AccountContract find(AccountOwner $owner, string $type = null, Currency $currency = null)
 * @method static AccountContract get(string $uuid)
 * @method static Enumerable|AccountContract[] all(AccountOwner $owner = null)
 * @method static AccountPair pair(AccountContract $origin, AccountContract $destination)
 * @method static AccountContract locate(mixed $address)
 * @method static Collection|Money[] totalPerAccount()
 * @method static Collection|Money[] invalidTotalPerAccount()
 * @method static bool isValid(bool $aggressive = false)
 *
 * @see \TTBooking\Accounting\AccountManager::class
 */
class Account extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TTBooking\Accounting\Contracts\AccountManager::class;
    }
}
