<?php

return [

    'rounding_mode' => Money\Money::ROUND_HALF_UP,

    'owner' => [
        'default_type' => env('ACCOUNTING_OWNER_DEFAULT_TYPE', 'user'),
        'mappings' => [
            'user' => ['default'],
            'order' => ['paid'],
        ],
    ],

    'account' => [
        'default_type' => env('ACCOUNTING_ACCOUNT_DEFAULT_TYPE', 'default'),
        'default_currency' => env('ACCOUNTING_ACCOUNT_DEFAULT_CURRENCY', 'USD'),
        'auto_create' => env('ACCOUNTING_ACCOUNT_AUTO_CREATE', false),
        'use_money_calculator' => env('ACCOUNTING_ACCOUNT_USE_MONEY_CALCULATOR', false),
        'type_owner_mappings' => [
            //'order' => ...
        ],
        'default_owner_mapping' => '',
        'allowed_types' => [],
        'allowed_currencies' => [],
    ],

    'transaction' => [
        'default_currency' => env('ACCOUNTING_TRANSACTION_DEFAULT_CURRENCY', 'origin'),
        'auto_commit' => env('ACCOUNTING_TRANSACTION_AUTO_COMMIT', false),
        'commit_attempts' => env('ACCOUNTING_TRANSACTION_COMMIT_ATTEMPTS', 1),
        'allow_zero_transfers' => env('ACCOUNTING_TRANSACTION_ALLOW_ZERO_TRANSFERS', false),
    ],

];
