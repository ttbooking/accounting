<?php

declare(strict_types=1);

namespace TTBooking\Accounting\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait HasConfigurableName
{
    public function getNameSource(): ?string
    {
        return $this->nameSource ?? null;
    }

    protected function initializeHasConfigurableName(): void
    {
        foreach ($this->suggestNameSources() as $nameSource) {
            if ($table = Config::get($nameSource)) {
                $this->setTable($table);
                return;
            }
        }
    }

    private function suggestNameSources(): array
    {
        $suggestions = ['database.model_table_mapping.'.static::class];

        if ($nameSource = $this->getNameSource()) {
            $suggestions[] = $nameSource;
        } elseif (count($components = explode('\\', static::class)) > 2 || $components[0] !== 'App') {
            $suggestions[] = Str::kebab($components[1]).'.'.Str::snake(end($components).'_table');
        }

        return $suggestions;
    }
}
