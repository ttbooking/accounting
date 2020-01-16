<?php

return [

    'rounding_mode' => \Money\Money::ROUND_HALF_UP,

    'account' => [
        'default_type' => env('ACCOUNTING_ACCOUNT_DEFAULT_TYPE', 'default'),
        'default_currency' => env('ACCOUNTING_ACCOUNT_DEFAULT_CURRENCY', 'USD'),
        'auto_create' => env('ACCOUNTING_ACCOUNT_AUTO_CREATE', false),
        'use_money_calculator' => env('ACCOUNTING_ACCOUNT_USE_MONEY_CALCULATOR', false),
        'type_owner_mappings' => [
            //'order' => ...
        ],
        'default_owner_mapping' => '',
    ],

    'transaction' => [
        'default_currency' => env('ACCOUNTING_TRANSACTION_DEFAULT_CURRENCY', 'USD'), // source/destination
        'auto_commit' => env('ACCOUNTING_TRANSACTION_AUTO_COMMIT', false),
        'commit_attempts' => env('ACCOUNTING_TRANSACTION_COMMIT_ATTEMPTS', 1),
    ],

];
