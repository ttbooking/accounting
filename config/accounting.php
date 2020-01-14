<?php

return [

    'rounding_mode' => \Money\Money::ROUND_HALF_UP,

    'account' => [
        'default_type' => 'default',
        'default_currency' => 'EUR',
        'auto_create' => false,
        'type_owner_mappings' => [
            //'order' => ...
        ],
        'default_owner_mapping' => '',
    ],

    'transaction' => [
        'default_currency' => 'EUR', // source/destination
        'auto_commit' => false,
        'commit_attempts' => 1,
    ],

];
