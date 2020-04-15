<?php

namespace Daniser\Accounting;

use Illuminate\Contracts\Events\Dispatcher;
use Money\Converter;
use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;

class Ledger implements Contracts\Ledger
{
    /** @var array */
    protected array $config;

    /** @var Dispatcher|null */
    protected ?Dispatcher $dispatcher;

    /** @var MoneyFormatter|null */
    protected ?MoneyFormatter $serializer;

    /** @var MoneyParser|null */
    protected ?MoneyParser $deserializer;

    /** @var MoneyFormatter|null */
    protected ?MoneyFormatter $formatter;

    /** @var MoneyParser|null */
    protected ?MoneyParser $parser;

    /** @var Converter|null */
    protected ?Converter $converter;

    /**
     * Ledger constructor.
     *
     * @param array $config
     * @param Dispatcher|null $dispatcher
     * @param MoneyFormatter|null $serializer
     * @param MoneyParser|null $deserializer
     * @param MoneyFormatter|null $formatter
     * @param MoneyParser|null $parser
     * @param Converter|null $converter
     */
    public function __construct(
        array $config = [],
        Dispatcher $dispatcher = null,
        MoneyFormatter $serializer = null,
        MoneyParser $deserializer = null,
        MoneyFormatter $formatter = null,
        MoneyParser $parser = null,
        Converter $converter = null
    ) {
        $this->config = $config;
        $this->dispatcher = $dispatcher;
        $this->serializer = $serializer;
        $this->deserializer = $deserializer;
        $this->formatter = $formatter ?? $serializer;
        $this->parser = $parser ?? $deserializer;
        $this->converter = $converter;
    }

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

    public function serializeMoney(Money $money): string
    {
        if (! $this->serializer) {
            return $money->getAmount();
        }

        return $this->serializer->format($money);
    }

    public function deserializeMoney(string $money, Currency $forceCurrency = null): Money
    {
        if (! $this->deserializer) {
            return new Money($money, $forceCurrency ?? new Currency($this->config['account']['default_currency']));
        }

        return $this->deserializer->parse($money, $forceCurrency);
    }

    public function formatMoney(Money $money): string
    {
        if (! $this->formatter) {
            return $money->getAmount();
        }

        return $this->formatter->format($money);
    }

    public function parseMoney(string $money, Currency $forceCurrency = null): Money
    {
        if (! $this->parser) {
            return new Money($money, $forceCurrency ?? new Currency($this->config['account']['default_currency']));
        }

        return $this->parser->parse($money, $forceCurrency);
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
}
