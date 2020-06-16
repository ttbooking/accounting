<?php

namespace TTBooking\Accounting\Concerns;

trait OperatesMoney
{
    use HasAccounts, ForwardsCallsToAccount;
}
