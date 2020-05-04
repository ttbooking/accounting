<?php

namespace Daniser\Accounting;

use Closure;
use Daniser\Accounting\Contracts\SafeMoneyParser;
use Daniser\Accounting\Support\FallbackMoneyParser;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Money\Converter;
use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;
use Money\MoneyParser;

class Ledger implements Contracts\Ledger
{
    /** @var array */
    protected array $config;

    /** @var DatabaseManager */
    protected DatabaseManager $db;

    /** @var Dispatcher|null */
    protected ?Dispatcher $dispatcher;

    /** @var MoneyFormatter|null */
    protected ?MoneyFormatter $serializer;

    /** @var SafeMoneyParser|null */
    protected ?SafeMoneyParser $deserializer;

    /** @var MoneyFormatter|null */
    protected ?MoneyFormatter $formatter;

    /** @var SafeMoneyParser|null */
    protected ?SafeMoneyParser $parser;

    /** @var Converter|null */
    protected ?Converter $converter;

    /**
     * Ledger constructor.
     *
     * @param array $config
     * @param DatabaseManager $db
     * @param Dispatcher|null $dispatcher
     * @param MoneyFormatter|null $serializer
     * @param MoneyParser|null $deserializer
     * @param MoneyFormatter|null $formatter
     * @param MoneyParser|null $parser
     * @param Converter|null $converter
     */
    public function __construct(
        array $config,
        DatabaseManager $db,
        Dispatcher $dispatcher = null,
        MoneyFormatter $serializer = null,
        MoneyParser $deserializer = null,
        MoneyFormatter $formatter = null,
        MoneyParser $parser = null,
        Converter $converter = null
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->dispatcher = $dispatcher;
        $this->serializer = $serializer;
        $this->deserializer = self::decorateParser($deserializer);
        $this->formatter = $formatter ?? $serializer;
        $this->parser = self::decorateParser($parser ?? $deserializer);
        $this->converter = $converter;
    }

    public function transaction(Closure $callback, $attempts = null)
    {
        return $this->db->transaction($callback, $attempts ?? $this->config['transaction']['commit_attempts']);
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

    public function deserializeMoney(string $money, Currency $fallbackCurrency = null): Money
    {
        $fallbackCurrency ??= new Currency($this->config['account']['default_currency']);

        if (! $this->deserializer) {
            return new Money($money, $fallbackCurrency);
        }

        return $this->deserializer->parse($money, $fallbackCurrency);
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
        $fallbackCurrency ??= new Currency($this->config['account']['default_currency']);

        if (! $this->parser) {
            return new Money($money, $fallbackCurrency);
        }

        return $this->parser->parse($money, $fallbackCurrency);
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

    /**
     * @param MoneyParser|null $parser
     *
     * @return SafeMoneyParser|null
     */
    private static function decorateParser(?MoneyParser $parser)
    {
        return $parser instanceof SafeMoneyParser || is_null($parser) ? $parser : new FallbackMoneyParser($parser);
    }
}
