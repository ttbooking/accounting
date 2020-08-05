<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Owner Type
    |--------------------------------------------------------------------------
    |
    | Here you may specify the type of an owner, which will be used
    | by the entity locator when owner part of the account address is omitted.
    | It can be class name, entity alias or morph map alias.
    |
    | For details, check Entity Locator documentation.
    |
    */

    'default_owner_type' => env('ACCOUNTING_DEFAULT_OWNER_TYPE', 'user'),

    /*
    |--------------------------------------------------------------------------
    | Account Table
    |--------------------------------------------------------------------------
    |
    | Here you may define the database table name used for account storage.
    | Make sure to do this before applying package's migrations.
    |
    */

    'account_table' => env('ACCOUNTING_ACCOUNT_TABLE', 'accounting_accounts'),

    /*
    |--------------------------------------------------------------------------
    | Default Account Type
    |--------------------------------------------------------------------------
    |
    | Here you may specify the type of an account, which will be used
    | for account location and creation when it's not specified by the caller.
    |
    | Max length is 36 characters.
    |
    */

    'default_account_type' => env('ACCOUNTING_DEFAULT_ACCOUNT_TYPE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Default Account Currency
    |--------------------------------------------------------------------------
    |
    | Here you may specify the currency of an account, which will be used
    | for account location and creation when it's not specified by the caller.
    |
    | Use alphabetic ISO 4217 code.
    |
    */

    'default_account_currency' => env('ACCOUNTING_DEFAULT_ACCOUNT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Account Auto Creation
    |--------------------------------------------------------------------------
    |
    | This option controls how the system will behave when desired account
    | cannot be found. By default, it throws an exception. Here you can
    | reconfigure it to automatically create accounts if they doesn't exist.
    |
    */

    'auto_create_accounts' => env('ACCOUNTING_AUTO_CREATE_ACCOUNTS', false),

    /*
    |--------------------------------------------------------------------------
    | Transaction Table
    |--------------------------------------------------------------------------
    |
    | Here you may define the database table name used as transaction ledger.
    | Make sure to do this before applying package's migrations.
    |
    */

    'transaction_table' => env('ACCOUNTING_TRANSACTION_TABLE', 'accounting_transactions'),

    /*
    |--------------------------------------------------------------------------
    | Base Transaction Currency
    |--------------------------------------------------------------------------
    |
    | This option defines the transaction ledger's base currency.
    | If you need to change base currency of the existing non-empty ledger,
    | you will need to issue the transaction:rebase command thereafter.
    |
    | Use alphabetic ISO 4217 code.
    |
    */

    'base_transaction_currency' => env('ACCOUNTING_BASE_TRANSACTION_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default Transaction Currency
    |--------------------------------------------------------------------------
    |
    | Here you may specify the currency of an transaction, which will be used
    | on transaction creation when it's not specified by the caller.
    |
    | Supported: "base", "origin", "destination", or alphabetic ISO 4217 code.
    |
    */

    'default_transaction_currency' => env('ACCOUNTING_DEFAULT_TRANSACTION_CURRENCY', 'origin'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Auto Commit
    |--------------------------------------------------------------------------
    |
    | This option allows you to force instant commit of new transactions.
    | Disabled by default.
    |
    */

    'auto_commit_transactions' => env('ACCOUNTING_AUTO_COMMIT_TRANSACTIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Transaction Commit Attempts
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of commit attempts before throwing
    | an exception in case of failure.
    |
    */

    'transaction_commit_attempts' => env('ACCOUNTING_TRANSACTION_COMMIT_ATTEMPTS', 1),

    /*
    |--------------------------------------------------------------------------
    | Zero Amount Transaction Control
    |--------------------------------------------------------------------------
    |
    | Enabling this option allows you to transfer zero amounts between
    | accounts.
    |
    */

    'allow_zero_transfers' => env('ACCOUNTING_ALLOW_ZERO_TRANSFERS', false),

    /*
    |--------------------------------------------------------------------------
    | Negative Amount Transaction Control
    |--------------------------------------------------------------------------
    |
    | Enabling this option allows you to pass negative amounts during
    | transaction creation; in such case system will swap origin and
    | destination accounts and transfer absolute amount between them.
    |
    */

    'handle_negative_amounts' => env('ACCOUNTING_HANDLE_NEGATIVE_AMOUNTS', false),

    /*
    |--------------------------------------------------------------------------
    | Amount Conversion Stage
    |--------------------------------------------------------------------------
    |
    | This option determines the stage in which amount conversion between
    | originating account's currency and transaction currency will occur.
    | When this option is disabled, amount will be converted on commit.
    | Otherwise, the conversion will occur instantly on transaction creation.
    | It may be useful for long-standing transactions where currency rate
    | may change between transaction creation and commit.
    |
    */

    'origin_forward_conversion' => env('ACCOUNTING_ORIGIN_FORWARD_CONVERSION', false),

    /*
    |--------------------------------------------------------------------------
    | Blockchain Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify hashing algorithm and HMAC key for blockchain.
    |
    | Supported algorithms:
    | see https://www.php.net/manual/en/function.hash-hmac-algos.php
    |
    */

    'blockchain' => [
        'algorithm' => env('ACCOUNTING_BLOCKCHAIN_ALGORITHM', 'sha256'),
        'key' => env('ACCOUNTING_BLOCKCHAIN_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Calculator Preference
    |--------------------------------------------------------------------------
    |
    | Enabling this option forces system to prefer MoneyPHP calculations
    | over DBMS ones where possible.
    |
    */

    'prefer_money_calculator' => env('ACCOUNTING_PREFER_MONEY_CALCULATOR', false),

    /*
    |--------------------------------------------------------------------------
    | MoneyPHP Rounding Mode
    |--------------------------------------------------------------------------
    |
    | This option controls rounding behavior during currency conversion
    | and other money-related operations.
    |
    | Supported:
    | see http://moneyphp.org/en/stable/features/operation.html#rounding-modes
    |
    */

    'rounding_mode' => env('ACCOUNTING_ROUNDING_MODE', Money\Money::ROUND_HALF_UP),

    'account_schema_strict_mode' => env('ACCOUNTING_ACCOUNT_SCHEMA_STRICT_MODE', false),

    'account_schema' => [

        /*App\User::class => [
            'types' => [
                'default' => DefaultAccount::class,
            ],
            'currencies' => ['USD'],
        ],*/

    ],

    /*'transactions' => [
        'default' => DefaultTransaction::class,
    ],*/

];
