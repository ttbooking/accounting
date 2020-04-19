<?php

namespace Daniser\Accounting\Support;

use Daniser\Accounting\Contracts\Account;
use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Daniser\EntityResolver\Exceptions\EntityNotFoundException;
use Illuminate\Contracts\Config\Repository;
use Money\Currency;

class AccountResolver implements EntityResolver
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
        [$ownerType, $ownerId, $accountType, $accountCurrency] = explode(':', $id) + ['', '', '', ''];

        if ($ownerType === '') {
            $ownerType = $this->config->get('accounting.owner.default_type');
        }

        if ($accountType === '') {
            $accountType = $this->config->get('accounting.account.default_type');
        }

        if ($accountCurrency === '') {
            $accountCurrency = $this->config->get('accounting.account.default_currency');
        }

        try {
            return $this->resolver
                ->resolve($ownerType, $ownerId)
                ->getAccount($accountType, new Currency($accountCurrency));
        } catch (AccountNotFoundException $e) {
            throw new EntityNotFoundException("Account with address $id not found.", $e->getCode(), $e);
        }
    }
}
