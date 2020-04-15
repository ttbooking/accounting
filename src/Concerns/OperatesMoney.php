<?php

namespace Daniser\Accounting\Concerns;

trait OperatesMoney
{
    use HasAccounts, ForwardsCallsToAccount;
}
