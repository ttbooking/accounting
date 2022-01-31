<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Contracts;

use Illuminate\Support\Enumerable;

interface AccountPair extends Reportable
{
    public function inverse(): self;

    public function getOrigin(): Account;

    public function getDestination(): Account;

    /**
     * @param  bool  $bidirectional
     * @return Enumerable|Transaction[]
     */
    public function getTransactions(bool $bidirectional = false): Enumerable;
}
