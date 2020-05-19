<?php

namespace Daniser\Accounting\Concerns;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

trait HasConfigurableName
{
    public function getNameSource(): string
    {
        return $this->nameSource ?? self::suggestNameSource();
    }

    protected function initializeHasConfigurableName(): void
    {
        if ($table = Config::get($this->getNameSource())) {
            $this->setTable($table);
        }
    }

    private static function suggestNameSource(): string
    {
        $components = explode('\\', __CLASS__);
        $package = count($components) < 3 || $components[0] === 'App' ? 'App' : $components[1];
        $basename = end($components);

        return sprintf('%s.%s_table', Str::kebab($package), Str::snake($basename));
    }
}
