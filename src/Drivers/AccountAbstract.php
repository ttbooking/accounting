<?php

namespace Daniser\Accounting\Drivers;

use Daniser\Accounting\Contracts;
use Illuminate\Support\Str;
use Money\Money;

abstract class AccountAbstract implements Contracts\Account
{
    public function __call(string $name, array $arguments): Contracts\Transaction
    {
        /**
         * @var Money
         * @var array|null $payload
         */
        [$amount, $payload] = $arguments + [1 => null];

        if (! Str::startsWith($name, 'un')) {
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
