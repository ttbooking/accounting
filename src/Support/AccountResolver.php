<?php

namespace Daniser\Accounting\Support;

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityNotFoundException;
use Money\Currency;

class AccountResolver implements EntityResolver
{
    /** @var AccountOwnerResolver */
    protected AccountOwnerResolver $resolver;

    /** @var string */
    protected string $defaultOwnerType;

    /** @var string */
    protected string $defaultAccountType;

    /** @var Currency */
    protected Currency $defaultAccountCurrency;

    /** @var string */
    protected string $delimiter;

    /**
     * AccountResolver constructor.
     *
     * @param AccountOwnerResolver $resolver
     * @param string $defaultOwnerType
     * @param string $defaultAccountType
     * @param Currency $defaultAccountCurrency
     * @param string $delimiter
     */
    public function __construct(
        AccountOwnerResolver $resolver,
        string $defaultOwnerType,
        string $defaultAccountType,
        Currency $defaultAccountCurrency,
        string $delimiter = ':'
    ) {
        $this->resolver = $resolver;
        $this->defaultOwnerType = $defaultOwnerType;
        $this->defaultAccountType = $defaultAccountType;
        $this->defaultAccountCurrency = $defaultAccountCurrency;
        $this->delimiter = $delimiter;
    }

    public function resolve(string $type, $id): Account
    {
        [$address, $previous] = ((array) $id) + [1 => ''];

        [$ownerType, $ownerId, $accountType, $accountCurrency] =
            $this->bareAddr($address) + [
                2 => $this->defaultAccountType
            ] + $this->bareAddr($previous) + [
                0 => $this->defaultOwnerType,
                3 => $this->defaultAccountCurrency->getCode(),
            ];

        try {
            return $this->resolver
                ->resolve($ownerType, $ownerId)
                ->getAccount($accountType, new Currency($accountCurrency));
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
