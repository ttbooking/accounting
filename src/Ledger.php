<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Money\Converter;
use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;

class Ledger implements Contracts\Ledger
{
    /** @var array */
    protected array $config;

    /** @var Contracts\AccountOwnerResolver|null */
    protected ?Contracts\AccountOwnerResolver $resolver;

    /** @var Dispatcher|null */
    protected ?Dispatcher $dispatcher;

    /** @var MoneyParser|null */
    protected ?MoneyParser $parser;

    /** @var MoneyFormatter|null */
    protected ?MoneyFormatter $formatter;

    /** @var Converter|null */
    protected ?Converter $converter;

    /**
     * Ledger constructor.
     *
     * @param array $config
     * @param Contracts\AccountOwnerResolver|null $resolver
     * @param Dispatcher|null $dispatcher
     * @param MoneyParser|null $parser
     * @param MoneyFormatter|null $formatter
     * @param Converter|null $converter
     */
    public function __construct(
        array $config = [],
        Contracts\AccountOwnerResolver $resolver = null,
        Dispatcher $dispatcher = null,
        MoneyParser $parser = null,
        MoneyFormatter $formatter = null,
        Converter $converter = null
    ) {
        $this->config = $config;
        $this->resolver = $resolver;
        $this->dispatcher = $dispatcher;
        $this->parser = $parser;
        $this->formatter = $formatter;
        $this->converter = $converter;
    }

    /**
     * @param string $type
     * @param mixed $id
     *
     * @return Contracts\AccountOwner
     */
    public function resolveOwner(string $type, $id): Contracts\AccountOwner
    {
        if (! $this->resolver) {
            throw new \RuntimeException("Can't resolve entity: no resolver defined.");
        }

        return $this->resolver->resolve($type, $id);
    }

    public function locateAccount(string $address): Account
    {
        [$ownerType, $ownerId, $accountType, $accountCurrency] = explode(':', $address);

        if (! $ownerType) {
            $ownerType = $this->config['owner']['default_type'];
        }
        if (! $accountType) {
            $accountType = $this->config['account']['default_type'];
        }
        $accountCurrency = new Currency($accountCurrency ?: $this->config['account']['default_currency']);

        return $this->getAccount($this->resolveOwner($ownerType, $ownerId), $accountType, $accountCurrency);
    }

    /**
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     *
     * @return mixed
     */
    public function fireEvent($event, $payload = [], $halt = true)
    {
        if (! $this->dispatcher) {
            return $halt ? null : [];
        }
        if (is_string($event)) {
            $event = "accounting.$event";
        }

        return $this->dispatcher->dispatch($event, $payload, $halt);
    }

    /**
     * @param string $money
     * @param Currency|null $forceCurrency
     * @return Money
     */
    public function parseMoney(string $money, Currency $forceCurrency = null): Money
    {
        if (! $this->parser) {
            return new Money($money, $forceCurrency ?? new Currency($this->config['account']['default_currency']));
        }

        return $this->parser->parse($money, $forceCurrency);
    }

    /**
     * @param Money $money
     * @return string
     */
    public function formatMoney(Money $money): string
    {
        if (! $this->formatter) {
            return $money->getAmount();
        }

        return $this->formatter->format($money);
    }

    public function convertMoney(Money $money, Currency $counterCurrency, $roundingMode = null): Money
    {
        if ($money->getCurrency()->getCode() === $counterCurrency->getCode()) {
            return $money;
        }
        if (! $this->converter) {
            throw new \RuntimeException("Can't convert money: no converter available.");
        }
        $roundingMode ??= $this->config['rounding_mode'];

        return $this->converter->convert($money, $counterCurrency, $roundingMode);
    }

    public function getAccount(Contracts\AccountOwner $owner, $type = null, Currency $currency = null): Account
    {
        try {
            $type ??= $this->config['account']['default_type'];
            $currency ??= new Currency($this->config['account']['default_currency']);

            return new Account($this, Models\Account::findByRequisites(
                $owner->getOwnerType(), $owner->getIdentifier(), $type, $currency->getCode()
            ));
        } catch (ModelNotFoundException $e) {
            throw new AccountNotFoundException('Account with given parameters not found.', 0, $e);
        }
    }

    public function getTransaction($id): Transaction
    {
        try {
            return new Transaction($this, Models\Transaction::findOrFail($id));
        } catch (ModelNotFoundException $e) {
            throw new TransactionNotFoundException("Transaction with id $id not found.", 0, $e);
        }
    }

    public function newTransaction(Contracts\Account $source, Contracts\Account $destination, Money $amount, array $payload = null): Transaction
    {
        return tap(new Transaction($this, Models\Transaction::create([
            'source_id' => $source->getUniqueIdentifier(),
            'destination_id' => $destination->getUniqueIdentifier(),
            'amount' => $this->formatMoney($amount),
            'currency' => $amount->getCurrency()->getCode(),
            'payload' => $payload,
        ])), function (Transaction $transaction) {
            $this->config['transaction']['auto_commit'] && $transaction->commit();
        });
    }
}
