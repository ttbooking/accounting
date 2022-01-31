<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Support;

use TTBooking\Accounting\Contracts\AccountOwner;
use TTBooking\EntityLocator\Contracts\EntityLocator;
use TTBooking\EntityLocator\Exceptions\EntityTypeMismatchException;

class AccountOwnerLocator implements EntityLocator
{
    /** @var EntityLocator */
    protected EntityLocator $locator;

    /**
     * AccountOwnerLocator constructor.
     *
     * @param  EntityLocator  $locator
     */
    public function __construct(EntityLocator $locator)
    {
        $this->locator = $locator;
    }

    public function locate(string $type, $id): AccountOwner
    {
        $owner = $this->locator->locate($type, $id);

        if (! $owner instanceof AccountOwner) {
            throw new EntityTypeMismatchException("Invalid type: $type does not implement AccountOwner contract.");
        }

        return $owner;
    }
}
