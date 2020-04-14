<?php

namespace Daniser\Accounting\Contracts;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Money\Currency;
use Money\Money;

interface Ledger
{
    /**
     * @param Money $money
     * @param Currency $counterCurrency
     * @param int|null $roundingMode
     *
     * @return Money
     */
    public function convertMoney(Money $money, Currency $counterCurrency, $roundingMode = null): Money;

    /**
     * @param AccountOwner $owner
     * @param string|null $type
     * @param Currency|null $currency
     *
     * @throws AccountNotFoundException
     *
     * @return Account
     */
    public function getAccount(AccountOwner $owner, $type = null, Currency $currency = null): Account;

    /**
     * @param string $address
     *
     * @throws AccountNotFoundException
     *
     * @return Account
     */
    public function locateAccount(string $address): Account;

    /**
     * @param mixed $id
     *
     * @throws TransactionNotFoundException
     *
     * @return Transaction
     */
    public function getTransaction($id): Transaction;

    /**
     * @param Account $source
     * @param Account $destination
     * @param Money $amount
     * @param array|null $payload
     *
     * @return Transaction
     */
    public function newTransaction(Account $source, Account $destination, Money $amount, array $payload = null): Transaction;
}
