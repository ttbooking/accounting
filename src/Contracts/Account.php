<?php

namespace Daniser\Accounting\Contracts;

use Money\Currency;
use Money\Money;

/**
 * ->lock(...) := transfer from current account to owner's account type 'locked'
 * ->unlock(...) := transfer from owner's account type 'locked' to current account.
 */
interface Account
{
    public function getUniqueIdentifier();

    public function getType(): string;

    public function getOwner(): AccountOwner;

    public function getBalance(): Money;

    public function getCurrency(): Currency;

    public function getLimit(): Money;

    public function setLimit(Money $limit): void;

    /* if is_int($amount) -> use config('default_transaction_currency', SENDER | RECIPIENT) */

    /**
     * @param Account $recipient
     * @param Money $amount
     * @param array|null $payload
     *
     * @return Transaction
     */
    public function transfer(self $recipient, Money /*|int*/ $amount, array $payload = null): Transaction;
}
