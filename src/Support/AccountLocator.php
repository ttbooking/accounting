<?php

namespace TTBooking\Accounting\Support;

use Money\Currency;
use TTBooking\Accounting\Contracts\Account;
use TTBooking\Accounting\Exceptions\AccountNotFoundException;
use TTBooking\EntityLocator\Contracts\EntityLocator;
use TTBooking\EntityLocator\Exceptions\EntityNotFoundException;

class AccountLocator implements EntityLocator
{
    /** @var AccountOwnerLocator */
    protected AccountOwnerLocator $locator;

    /** @var string */
    protected string $defaultOwnerType;

    /** @var string */
    protected string $defaultAccountType;

    /** @var Currency */
    protected Currency $defaultAccountCurrency;

    /** @var string */
    protected string $delimiter;

    /**
     * AccountLocator constructor.
     *
     * @param AccountOwnerLocator $locator
     * @param string $defaultOwnerType
     * @param string $defaultAccountType
     * @param Currency $defaultAccountCurrency
     * @param string $delimiter
     */
    public function __construct(
        AccountOwnerLocator $locator,
        string $defaultOwnerType,
        string $defaultAccountType,
        Currency $defaultAccountCurrency,
        string $delimiter = ':'
    ) {
        $this->locator = $locator;
        $this->defaultOwnerType = $defaultOwnerType;
        $this->defaultAccountType = $defaultAccountType;
        $this->defaultAccountCurrency = $defaultAccountCurrency;
        $this->delimiter = $delimiter;
    }

    public function locate(string $type, $id): Account
    {
        [$address, $previous] = ((array) $id) + [1 => ''];

        [$ownerType, $ownerId, $accountType, $accountCurrency] =
            $this->bareAddr($address) + [
                2 => $this->defaultAccountType,
            ] + $this->bareAddr($previous) + [
                0 => $this->defaultOwnerType,
                1 => '',
                3 => $this->defaultAccountCurrency->getCode(),
            ];

        try {
            return $this->locator
                ->locate($ownerType, $ownerId)
                ->getAccount($accountType, new Currency(strtoupper($accountCurrency)));
        } catch (AccountNotFoundException $e) {
            throw new EntityNotFoundException("Account with address $id not found.", $e->getCode(), $e);
        }
    }

    /**
     * @param string $address
     * @return string[]
     */
    private function bareAddr(string $address)
    {
        return array_filter(explode($this->delimiter, $address), 'strlen');
    }
}
