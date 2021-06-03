<?php

declare(strict_types=1);

namespace TTBooking\Accounting;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Money\Converter;
use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;
use TTBooking\ClassFactory\ClassFactoryException;
use TTBooking\MoneySerializer\Contracts\SerializesMoney;

class Ledger implements Contracts\Ledger
{
    /** @var Repository */
    protected Repository $config;

    /** @var DatabaseManager */
    protected DatabaseManager $db;

    /** @var SerializesMoney */
    protected SerializesMoney $serializer;

    /** @var MoneyFormatter|null */
    protected ?MoneyFormatter $formatter;

    /** @var MoneyParser|null */
    protected ?MoneyParser $parser;

    /** @var Converter|null */
    protected ?Converter $converter;

    /** @var Dispatcher|null */
    protected ?Dispatcher $dispatcher;

    /**
     * Ledger constructor.
     *
     * @param Repository $config
     * @param DatabaseManager $db
     * @param SerializesMoney $serializer
     * @param MoneyFormatter|null $formatter
     * @param MoneyParser|null $parser
     * @param Converter|null $converter
     * @param Dispatcher|null $dispatcher
     */
    public function __construct(
        Repository $config,
        DatabaseManager $db,
        SerializesMoney $serializer,
        MoneyFormatter $formatter = null,
        MoneyParser $parser = null,
        Converter $converter = null,
        Dispatcher $dispatcher = null
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->dispatcher = $dispatcher;
        $this->serializer = $serializer;
        $this->formatter = $formatter;
        $this->parser = $parser;
        $this->converter = $converter;
    }

    public function transaction(Closure $callback, int $attempts = null)
    {
        $attempts ??= $this->config->get('accounting.transaction_commit_attempts');

        return $this->db->transaction($callback, $attempts);
    }

    public function fireEvent($event, array $payload = [], bool $halt = true)
    {
        if (! $this->dispatcher) {
            return $halt ? null : [];
        }
        if (is_string($event)) {
            $event = "accounting.$event";
            try {
                $event = new_class($event)(...$payload);
            } catch (ClassFactoryException $e) {
            }
        }

        return $this->dispatcher->dispatch($event, $payload, $halt);
    }

    public function serializeMoney(Money $money): string
    {
        return $this->serializer->serialize($money);
    }

    public function deserializeMoney(string $money, Currency $fallbackCurrency = null): Money
    {
        return $this->serializer->deserialize($money, $fallbackCurrency);
    }

    public function formatMoney(Money $money): string
    {
        if (! $this->formatter) {
            return $money->getAmount();
        }

        return $this->formatter->format($money);
    }

    public function parseMoney(string $money, Currency $fallbackCurrency = null): Money
    {
        $fallbackCurrency ??= new Currency($this->config->get('accounting.default_account_currency'));

        if (! $this->parser) {
            return new Money($money, $fallbackCurrency);
        }

        return $this->parser->parse($money, $fallbackCurrency);
    }

    public function convertMoney(Money $money, Currency $counterCurrency, int $roundingMode = null): Money
    {
        if ($money->getCurrency()->getCode() === $counterCurrency->getCode()) {
            return $money;
        }
        if (! $this->converter) {
            throw new \RuntimeException("Can't convert money: no converter available.");
        }
        $roundingMode ??= $this->config->get('accounting.rounding_mode');

        return $this->converter->convert($money, $counterCurrency, $roundingMode);
    }
}
