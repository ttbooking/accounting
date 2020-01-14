<?php

namespace Daniser\Accounting;

use Money\Money;
use Illuminate\Support\Str;

abstract class AccountAbstract implements Contracts\Account
{
    public function __call(string $name, array $arguments): Contracts\Transaction
    {
        /**
         * @var Money $amount
         * @var array|null $payload
         */
        [$amount, $payload] = $arguments;

        if (!Str::startsWith($name, 'un')) {
            return $this->transfer(
                $this->getOwner()->getAccount("{$name}ed", $amount->getCurrency()),
                $amount, $payload
            );
        } else {
            $name = Str::substr($name, 2);
            return $this->getOwner()->getAccount("{$name}ed", $amount->getCurrency())
                ->transfer($this, $amount, $payload);
        }
    }
}
