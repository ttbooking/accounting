<?php

namespace Daniser\Accounting\Support;

use Daniser\Accounting\Contracts;
use Illuminate\Contracts\Config\Repository;
use Money\Currency;

class AccountLocator implements Contracts\AccountLocator
{
    /** @var Contracts\AccountOwnerResolver $resolver */
    protected Contracts\AccountOwnerResolver $resolver;

    /** @var Repository $config */
    protected Repository $config;

    /**
     * AccountLocator constructor.
     *
     * @param Contracts\AccountOwnerResolver $resolver
     * @param Repository $config
     */
    public function __construct(Contracts\AccountOwnerResolver $resolver, Repository $config)
    {
        $this->resolver = $resolver;
        $this->config = $config;
    }

    public function locate(string $address): Contracts\Account
    {
        [$ownerType, $ownerId, $accountType, $accountCurrency] = explode(':', $address);

        if ($ownerType === '') {
            $this->config->get('accounting.owner.default_type');
        }

        if ($accountType === '') {
            $this->config->get('accounting.account.default_type');
        }

        if ($accountCurrency === '') {
            $this->config->get('accounting.account.default_currency');
        }

        return $this->resolver
            ->resolve($ownerType, $ownerId)
            ->getAccount($accountType, new Currency($accountCurrency));
    }
}
