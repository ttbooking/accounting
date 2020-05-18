<?php

namespace Daniser\Accounting\Concerns;

use Daniser\Accounting\Facades\Ledger;
use Illuminate\Support\Str;

trait HasConfigurableName
{
    public function getNameSource(): string
    {
        return $this->nameSource ?? Str::snake(class_basename($this)).'.table';
    }

    protected function initializeHasConfigurableName()
    {
        if ($table = Ledger::config($this->getNameSource())) {
            $this->setTable($table);
        }
    }
}
