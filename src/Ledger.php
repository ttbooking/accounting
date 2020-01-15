<?php

namespace Daniser\Accounting;

use Daniser\Accounting\Exceptions\AccountNotFoundException;
use Daniser\Accounting\Exceptions\TransactionNotFoundException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Money\Converter;
use Money\Currency;
use Money\Money;

class Ledger implements Contracts\Ledger
{
    /** @var array $config */
    protected array $config;

    /** @var Dispatcher|null $dispatcher */
    protected ?Dispatcher $dispatcher;

    /** @var Converter|null $converter */
    protected ?Converter $converter;

    /**
     * Ledger constructor.
     *
     * @param array $config
     * @param Dispatcher|null $dispatcher
     * @param Converter|null $converter
     */
    public function __construct(array $config = [], Dispatcher $dispatcher = null, Converter $converter = null)
    {
        $this->config = $config;
        $this->dispatcher = $dispatcher;
        $this->converter = $converter;
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
        if (! isset($this->dispatcher)) {
            return $halt ? null : [];
        }
        if (is_string($event)) {
            $event = "accounting.$event";
        }

        return $this->dispatcher->dispatch($event, $payload, $halt);
    }

    public function convertMoney(Money $money, Currency $counterCurrency, $roundingMode = null): Money
    {
        if ($money->getCurrency()->getCode() === $counterCurrency->getCode()) {
            return $money;
        }
        if (! $this->converter) {
            throw new \RuntimeException("Can't convert money: no converter available.");
        }
        $roundingMode = $roundingMode ?? $this->getRoundingMode();

        return $this->converter->convert($money, $counterCurrency, $roundingMode);
    }

    public function getAccount(Contracts\AccountOwner $owner, $type = null, Currency $currency = null): Contracts\Account
    {
        try {
            $type = $type ?? $this->getDefaultType();
            $currency = $currency ?? $this->getDefaultCurrency();

            return new Account($this, Models\Account::findByRequisites(
                $owner->getIdentifier(), $type, $currency->getCode()
            ));
        } catch (ModelNotFoundException $e) {
            throw new AccountNotFoundException('Account with given parameters not found.', 0, $e);
        }
    }

    public function getTransaction($id): Contracts\Transaction
    {
        try {
            return new Transaction($this, Models\Transaction::findOrFail($id));
        } catch (ModelNotFoundException $e) {
            throw new TransactionNotFoundException("Transaction with id $id not found.", 0, $e);
        }
    }

    public function newTransaction(Contracts\Account $source, Contracts\Account $destination, Money /*|int*/ $amount, array $payload = null): Transaction
    {
        return tap(new Transaction($this, Models\Transaction::create([
            'source_id' => $source->getUniqueIdentifier(),
            'destination_id' => $destination->getUniqueIdentifier(),
            'amount' => $amount->getAmount(),
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
