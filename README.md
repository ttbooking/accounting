# Accounting library
This Laravel package provides support for robust, transactional money or asset transfers between virtual accounts.

## Features
- DBMS based transactions and pessimistic locking
- UUIDs for seamless integration and extension
- Big integer and arbitrary precision math support via MoneyPHP library
- Currency conversion support
- Blockchain for data integrity checking
- Complete control through console commands

## Requirements
PHP 7.4 and Laravel 6.0 at least.  
RDBMS with native JSON field support (recommended).

## Installation
Using Composer:
```
$ composer require ttbooking/accounting
```

## Configuration
After installation you'll need to configure package, in which case you have 2 options:
- via environment variables (your application's `.env` file)
- using package's configuration file

If you choose second option, you'll have to copy configuration file into your app's config directory by issuing following command:
```
$ artisan vendor:publish --provider=TTBooking\Accounting\AccountingServiceProvider --tag=config
```
You can look into `accounting.php` config file if you need to know more about each option and its usage.

If you need to alter database table names or modify schema, you'll also need to issue following command:
```
$ artisan vendor:publish --provider=TTBooking\Accounting\AccountingServiceProvider --tag=migrations
```

If you need to publish both config file and database migrations, you can omit "--tag" option:
```
$ artisan vendor:publish --provider=TTBooking\Accounting\AccountingServiceProvider
```

After all database-related modifications are done (if needed), you'll need to execute `artisan migrate` command.

It is recommended to also configure morph map for every possible account owner entity type.  
See https://laravel.com/docs/6.x/eloquent-relationships#custom-polymorphic-types for more info.

## Usage
To create account and link it to existing entity, you can use Account facade (or corresponding AccountManager interface):
```
$account = Account::create($user);
```
... will create account for user `$user`.  

To find existing account by its owner, try this:
```
$account = Account::find($user);
```
... will find account of `$user` if it exists or fail otherwise.  
*Note*: it won't fail if you've enabled account auto-creation. Instead, it will create missing account.

To transfer money between two accounts, you can use Transaction facade (or corresponding TransactionManager interface):
```
$transaction = Transaction::create($account1, $account2, new Money(10000, new Currency('USD')));
```
... will create transaction for transferring $100 from `$account1` to `$account2`.  

When you'll need to commit this transaction, just do `$transaction->commit();`.  
You can configure autocommit feature if separate transaction create/commit operation is not needed in your project.

To retrieve account or transaction by its UUID, try following:
```
$account = Account::get($uuid);
```
... to get account or
```
$transaction = Transaction::get($uuid);
```
... to get transaction.
