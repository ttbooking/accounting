<?php

namespace Daniser\Accounting\Support;

use Daniser\Accounting\Contracts;
use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Contracts\AccountOwnerResolver;
use Illuminate\Contracts\Config\Repository;
use Money\Currency;

class AccountResolver implements Contracts\AccountResolver
{
    /** @var AccountOwnerResolver */
    protected AccountOwnerResolver $resolver;

    /** @var Repository */
    protected Repository $config;

    /**
     * AccountResolver constructor.
     *
     * @param AccountOwnerResolver $resolver
     * @param Repository $config
     */
    public function __construct(AccountOwnerResolver $resolver, Repository $config)
    {
        $this->resolver = $resolver;
        $this->config = $config;
    }

    public function resolve(string $type, $id): Account
    {
        [$ownerType, $ownerId, $accountType, $accountCurrency] = explode(':', $id);

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
