<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Concerns;

trait OperatesMoney
{
    use HasAccounts, ForwardsCallsToAccount;
}
