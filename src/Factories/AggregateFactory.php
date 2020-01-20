<?php

namespace Daniser\Accounting\Factories;

use Daniser\Accounting\Contracts\OwnerFactory;
use Daniser\Accounting\Contracts\AccountOwner;
use Daniser\Accounting\Exceptions;
use Illuminate\Contracts\Container\Container;

class AggregateFactory implements OwnerFactory
{
    /** @var Container $container */
    protected Container $container;

    /** @var string[] $factories */
    protected array $factories;

    /**
     * AggregateFactory constructor.
     *
     * @param Container $container
     * @param string[] $factories
     */
    public function __construct(Container $container, array $factories = [])
    {
        $this->container = $container;
        $this->factories = $factories;
    }

    public function getOwner(string $type, $id): AccountOwner
    {
        if (! is_subclass_of($type, AccountOwner::class)) {
            throw new Exceptions\OwnerTypeMismatchException("Invalid type: $type cannot be resolved.");
        }

        if (! array_key_exists($type, $this->factories)) {
            throw new Exceptions\OwnerResolutionException("Unresolvable type: $type cannot be resolved.");
        }

        /** @var OwnerFactory $factory */
        if (! is_subclass_of($factory = $this->factories[$type], OwnerFactory::class)) {
            throw new Exceptions\FactoryException("Invalid factory: $factory is not a factory.");
        }

        return $factory->getOwner($type, $id);
    }
}
