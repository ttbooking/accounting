<?php

namespace Daniser\Accounting\Drivers\Eloquent;

use Daniser\Accounting\Contracts;
use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Daniser\Accounting\Models;
use Daniser\EntityResolver\Contracts\EntityResolver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Money\Converter;
use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;

class Ledger implements Contracts\Ledger
{
    /** @var array $config */
    protected array $config;

    /** @var EntityResolver|null $resolver */
    protected ?EntityResolver $resolver;

    /** @var Dispatcher|null $dispatcher */
    protected ?Dispatcher $dispatcher;

    /** @var MoneyParser|null $parser */
    protected ?MoneyParser $parser;

    /** @var MoneyFormatter|null $formatter */
    protected ?MoneyFormatter $formatter;

    /** @var Converter|null $converter */
    protected ?Converter $converter;

    /**
     * Ledger constructor.
     *
     * @param array $config
     * @param EntityResolver|null $resolver
     * @param Dispatcher|null $dispatcher
     * @param MoneyParser|null $parser
     * @param MoneyFormatter|null $formatter
     * @param Converter|null $converter
     */
    public function __construct(
        array $config = [],
        EntityResolver $resolver = null,
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

        return $this->getAccount($this->resolveOwner($ownerType, $ownerId), $accountType, new Currency($accountCurrency));
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
            return new Money($money, $forceCurrency ?? $this->getDefaultCurrency());
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
        $roundingMode ??= $this->getRoundingMode();

        return $this->converter->convert($money, $counterCurrency, $roundingMode);
    }

    public function getAccount(Contracts\AccountOwner $owner, $type = null, Currency $currency = null): Account
    {
        try {
            $type ??= $this->getDefaultType();
            $currency ??= $this->getDefaultCurrency();

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
            $this->getAutoCommit() && $transaction->commit();
        });
    }

    public function getRoundingMode(): int
    {
        return $this->config['rounding_mode'];
    }

    public function setRoundingMode(int $type): void
    {
        $this->config['rounding_mode'] = $type;
    }

    public function getDefaultType(): string
    {
        return $this->config['account']['default_type'];
    }

    public function setDefaultType(string $type): void
    {
        $this->config['account']['default_type'] = $type;
    }

    public function getDefaultCurrency(): Currency
    {
        return new Currency($this->config['account']['default_currency']);
    }

    public function setDefaultCurrency(Currency $currency): void
    {
        $this->config['account']['default_currency'] = $currency->getCode();
    }

    public function getUseMoneyCalculator(): bool
    {
        return $this->config['account']['use_money_calculator'];
    }

    public function setUseMoneyCalculator(bool $useMoneyCalculator): void
    {
        $this->config['account']['use_money_calculator'] = $useMoneyCalculator;
    }

    public function getAutoCommit(): bool
    {
        return $this->config['transaction']['auto_commit'];
    }

    public function setAutoCommit(bool $autoCommit): void
    {
        $this->config['transaction']['auto_commit'] = $autoCommit;
    }

    public function getCommitAttempts(): int
    {
        return $this->config['transaction']['commit_attempts'];
    }

    public function setCommitAttempts(int $attempts): void
    {
        $this->config['transaction']['commit_attempts'] = $attempts;
    }
}
