<?php

namespace Daniser\Accounting\Concerns;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

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
        $suggestions = ['database.model_table_mapping.'.__CLASS__];

        if ($nameSource = $this->getNameSource()) {
            $suggestions[] = $nameSource;
        } elseif (count($components = explode('\\', __CLASS__)) > 2 || $components[0] !== 'App') {
            $suggestions[] = Str::kebab($components[1]).'.'.Str::snake(end($components).'_table');
        }

        return $suggestions;
    }
}
