<?php

namespace Daniser\Accounting\Facades;

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\Transaction as TransactionContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Facade;
use Money\Currency;
use Money\Money;

/**
 * @method static string getTable()
 * @method static Currency baseCurrency()
 * @method static Currency currency(Account $origin, Account $destination)
 * @method static string digest(TransactionContract $current, string $previousDigest = null)
 * @method static TransactionContract create(Account $origin, Account $destination, Money $amount, array $payload = null, TransactionContract $parent = null)
 * @method static TransactionContract get(string $uuid)
 * @method static Enumerable|TransactionContract[] all(bool $descending = false)
 * @method static Enumerable|TransactionContract[] uncommitted(bool $descending = false)
 * @method static Enumerable|TransactionContract[] committed(bool $descending = false)
 * @method static Enumerable|TransactionContract[] canceled(bool $descending = false)
 * @method static Enumerable|TransactionContract[] revertable(bool $descending = false)
 * @method static TransactionContract locate(mixed $address)
 * @method static Money total(\DateTimeInterface $byDate = null)
 * @method static Collection|Money[] incomePerAccount(\DateTimeInterface $byDate = null)
 * @method static Collection|Money[] expensePerAccount(\DateTimeInterface $byDate = null)
 * @method static Collection|Money[] totalPerAccount(\DateTimeInterface $byDate = null)
 *
 * @see \Daniser\Accounting\TransactionManager::class
 */
class Transaction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Daniser\Accounting\Contracts\TransactionManager::class;
    }
}
