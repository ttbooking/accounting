<?php

namespace Daniser\Accounting\Contracts;

interface ObjectResolver
{
    /**
     * @param  string  $type
     * @param  mixed  $id
     * @return mixed
     */
    public function resolve($type, $id);
}
