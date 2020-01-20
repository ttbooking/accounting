<?php

namespace Daniser\Accounting\Factories;

use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Contracts\OwnerFactory;
use Daniser\Accounting\Exceptions\OwnerTypeMismatchException;

class AliasFactory implements OwnerFactory
{
    /** @var OwnerFactory $factory */
    protected OwnerFactory $factory;

    /** @var string[] $aliases */
    protected array $aliases;

    /**
     * AliasFactory constructor.
     *
     * @param OwnerFactory $factory
     * @param string[] $aliases
     */
    public function __construct(OwnerFactory $factory, array $aliases = [])
    {
        $this->factory = $factory;
        $this->aliases = $aliases;
    }

    public function getOwner(string $type, $id): AccountOwner
    {
        try {
            return $this->factory->getOwner($type, $id);
        } catch (OwnerTypeMismatchException $e) {
            if (! array_key_exists($type, $this->aliases)) {
                throw $e;
            }

            return $this->factory->getOwner($this->aliases[$type], $id);
        }
    }
}
